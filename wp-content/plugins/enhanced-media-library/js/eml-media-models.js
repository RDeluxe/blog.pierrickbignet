window.wp = window.wp || {};

(function($){
	
	var media = wp.media;	
			
			
			
	media.model.Attachment = media.model.Attachment.extend({
		
		saveCompat: function( data, options ) {
			var model = this;

			// If we do not have the necessary nonce, fail immeditately.
			if ( ! this.get('nonces') || ! this.get('nonces').update ) {
				return $.Deferred().rejectWith( this ).promise();
			}

			// Clean queries' cache
			media.model.Query.cleanQueries();

			return media.post( 'save-attachment-compat', _.defaults({
				id:      this.id,
				nonce:   this.get('nonces').update,
				post_id: media.model.settings.post.id
			}, data ) ).done( function( resp, status, xhr ) {
				model.set( model.parse( resp, xhr ), options );
			});
		}
	}, {

		create: function( attrs ) {
			return media.model.Attachments.all.push( attrs );
		},

		get: _.memoize( function( id, attachment ) {
			return media.model.Attachments.all.push( attachment || { id: id } );
		})
	});
		
		
		
	media.model.Attachments = media.model.Attachments.extend({

		model: media.model.Attachment,
		
		parse: function( resp, xhr ) {
			
			if ( ! _.isArray( resp ) ) {
				resp = [resp];
			}

			return _.map( resp, function( attrs ) {
				var id, attachment, newAttributes;

				if ( attrs instanceof Backbone.Model ) {
					id = attrs.get( 'id' );
					attrs = attrs.attributes;
				} else {
					id = attrs.id;
				}

				attachment = media.model.Attachment.get( id );
				newAttributes = attachment.parse( attrs, xhr );

				if ( ! _.isEqual( attachment.attributes, newAttributes ) ) {
					attachment.set( newAttributes );
				}

				return attachment;
			});
		},
		
		_requery: function() {
			if ( this.props.get('query') ) {
				this.mirror( media.model.Query.get( this.props.toJSON() ) );
			}
		}
	});
		
		
		
	delete media.model.Attachments.all;
	media.model.Attachments.all = new media.model.Attachments();
	
	
	
	media.query = function( props ) {
		return new media.model.Attachments( null, {
			props: _.extend( _.defaults( props || {}, { orderby: 'date' } ), { query: true } )
		});
	};
	
	
	
	media.model.Query = media.model.Attachments.extend({
		/**
		 * @global wp.Uploader
		 *
		 * @param {Array} [models=[]] Array of models used to populate the collection.
		 * @param {Object} [options={}]
		 */
		initialize: function( models, options ) {
			var allowed;

			options = options || {};
			media.model.Attachments.prototype.initialize.apply( this, arguments );

			this.args     = options.args;
			this._hasMore = true;
			this.created  = new Date();

			this.filters.order = function( attachment ) {
				var orderby = this.props.get('orderby'),
					order = this.props.get('order');

				if ( ! this.comparator ) {
					return true;
				}

				// We want any items that can be placed before the last
				// item in the set. If we add any items after the last
				// item, then we can't guarantee the set is complete.
				if ( this.length ) {
					return 1 !== this.comparator( attachment, this.last(), { ties: true });

				// Handle the case where there are no items yet and
				// we're sorting for recent items. In that case, we want
				// changes that occurred after we created the query.
				} else if ( 'DESC' === order && ( 'date' === orderby || 'modified' === orderby ) ) {
					return attachment.get( orderby ) >= this.created;

				// If we're sorting by menu order and we have no items,
				// accept any items that have the default menu order (0).
				} else if ( 'ASC' === order && 'menuOrder' === orderby ) {
					return attachment.get( orderby ) === 0;
				}

				// Otherwise, we don't want any items yet.
				return false;
			};

			// Observe the central `wp.Uploader.queue` collection to watch for
			// new matches for the query.
			//
			// Only observe when a limited number of query args are set. There
			// are no filters for other properties, so observing will result in
			// false positives in those queries.
			allowed = [ 's', 'order', 'orderby', 'posts_per_page', 'post_mime_type', 'post_parent' ];
			if ( wp.Uploader && _( this.args ).chain().keys().difference( allowed ).isEmpty().value() ) {
				this.observe( wp.Uploader.queue );
			}
		},
		/**
		 * @returns {Boolean}
		 */
		hasMore: function() {
			return this._hasMore;
		},
		/**
		 * @param {Object} [options={}]
		 * @returns {Promise}
		 */
		more: function( options ) {
			var query = this;

			if ( this._more && 'pending' === this._more.state() ) {
				return this._more;
			}

			if ( ! this.hasMore() ) {
				return $.Deferred().resolveWith( this ).promise();
			}

			options = options || {};
			options.remove = false;

			return this._more = this.fetch( options ).done( function( resp ) {
				if ( _.isEmpty( resp ) || -1 === this.args.posts_per_page || resp.length < this.args.posts_per_page ) {
					query._hasMore = false;
				}
			});
		},
		/**
		 * Overrides Backbone.Collection.sync
		 * Overrides wp.media.model.Attachments.sync
		 *
		 * @param {String} method
		 * @param {Backbone.Model} model
		 * @param {Object} [options={}]
		 * @returns {Promise}
		 */
		sync: function( method, model, options ) {
			var args, fallback;

			// Overload the read method so Attachment.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action:  'query-attachments',
					post_id: media.model.settings.post.id
				});

				// Clone the args so manipulation is non-destructive.
				args = _.clone( this.args );

				// Determine which page to query.
				if ( -1 !== args.posts_per_page ) {
					args.paged = Math.floor( this.length / args.posts_per_page ) + 1;
				}

				options.data.query = args;
				return media.ajax( options );

			// Otherwise, fall back to Backbone.sync()
			} else {
				/**
				 * Call wp.media.model.Attachments.sync or Backbone.sync
				 */
				fallback = media.model.Attachments.prototype.sync ? media.model.Attachments.prototype : Backbone;
				return fallback.sync.apply( this, arguments );
			}
		}
	}, {
		/**
		 * @readonly
		 */
		defaultProps: {
			orderby: 'date',
			order:   'DESC'
		},
		/**
		 * @readonly
		 */
		defaultArgs: {
			posts_per_page: 40
		},
		/**
		 * @readonly
		 */
		orderby: {
			allowed:  [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo', 'id', 'post__in', 'menuOrder' ],
			valuemap: {
				'id':         'ID',
				'uploadedTo': 'parent',
				'menuOrder':  'menu_order ID'
			}
		},
		/**
		 * @readonly
		 */
		propmap: {
			'search':    's',
			'type':      'post_mime_type',
			'perPage':   'posts_per_page',
			'menuOrder': 'menu_order',
			'uploadedTo': 'post_parent'
		},
		
		queries: [],
		
		cleanQueries: function(){
			
			this.queries = [];
		},
		
		/**
		 * @static
		 * @method
		 *
		 * @returns {wp.media.model.Query} A new query.
		 */
		// Caches query objects so queries can be easily reused.
		get: (function(){

			/**
			 * @param {Object} props
			 * @param {Object} options
			 * @returns {Query}
			 */
			return function( props, options ) {
				var args     = {},
					orderby  = media.model.Query.orderby,
					defaults = media.model.Query.defaultProps,
					query,
					queries  = this.queries;;

				// Remove the `query` property. This isn't linked to a query,
				// this *is* the query.
				delete props.query;

				// Fill default args.
				_.defaults( props, defaults );

				// Normalize the order.
				props.order = props.order.toUpperCase();
				if ( 'DESC' !== props.order && 'ASC' !== props.order ) {
					props.order = defaults.order.toUpperCase();
				}

				// Ensure we have a valid orderby value.
				if ( ! _.contains( orderby.allowed, props.orderby ) ) {
					props.orderby = defaults.orderby;
				}

				// Generate the query `args` object.
				// Correct any differing property names.
				_.each( props, function( value, prop ) {
					if ( _.isNull( value ) ) {
						return;
					}

					args[ media.model.Query.propmap[ prop ] || prop ] = value;
				});

				// Fill any other default query args.
				_.defaults( args, media.model.Query.defaultArgs );

				// `props.orderby` does not always map directly to `args.orderby`.
				// Substitute exceptions specified in orderby.keymap.
				args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

				// Search the query cache for matches.
				query = _.find( queries, function( query ) {
					return _.isEqual( query.args, args );
				});

				// Otherwise, create a new query and add it to the cache.
				if ( ! query ) {
					query = new media.model.Query( [], _.extend( options || {}, {
						props: props,
						args:  args
					} ) );
					queries.push( query );
				}

				return query;
			};
		}())
	});
		
}(jQuery));