( function ( wp ) {
  const { registerBlockType } = wp.blocks;
  const { useState, useEffect } = wp.element;
  const { InspectorControls, useBlockProps } = wp.blockEditor;
  const {
    PanelBody,
    TextControl,
    SelectControl,
    CheckboxControl,
    RangeControl,
    Spinner,
    Notice,
  } = wp.components;
  const { __ } = wp.i18n;
  const apiFetch = wp.apiFetch;

  const FORMATS = [
    { label: __( 'Decimal', 'wp-odds-comparison' ), value: 'decimal' },
    { label: __( 'Fractional', 'wp-odds-comparison' ), value: 'fractional' },
    { label: __( 'American', 'wp-odds-comparison' ), value: 'american' },
  ];

  function Edit( { attributes, setAttributes } ) {
    const {
      sport,
      markets,
      bookmakers,
      oddsFormat,
      title,
      maxEvents,
    } = attributes;

    const [ preview, setPreview ] = useState( null );
    const [ loading, setLoading ] = useState( false );
    const [ error, setError ] = useState( '' );

    const blockProps = useBlockProps( { className: 'wpoc-block-editor' } );
    const catalog = ( window.wpocBlock && window.wpocBlock.bookmakers ) || [];
    const marketOptions = ( window.wpocBlock && window.wpocBlock.markets ) || [];

    function toggleBookmaker( key, checked ) {
      const next = checked
        ? [ ...bookmakers, key ]
        : bookmakers.filter( ( k ) => k !== key );
      setAttributes( { bookmakers: next } );
    }

    function toggleMarket( key, checked ) {
      const next = checked
        ? [ ...markets, key ]
        : markets.filter( ( k ) => k !== key );
      setAttributes( { markets: next.length ? next : [ 'h2h' ] } );
    }

    useEffect( () => {
      if ( ! sport || ! bookmakers.length ) {
        setPreview( null );
        return;
      }

      setLoading( true );
      setError( '' );

      const params = new URLSearchParams( {
        sport,
        markets: markets.join( ',' ),
        bookmakers: bookmakers.join( ',' ),
        odds_format: oddsFormat,
      } );

      apiFetch( { path: '/wpoc/v1/odds?' + params.toString() } )
        .then( ( data ) => {
          setPreview( data );
          setLoading( false );
        } )
        .catch( ( err ) => {
          setError( err.message || __( 'Failed to load odds.', 'wp-odds-comparison' ) );
          setLoading( false );
        } );
    }, [ sport, markets.join( ',' ), bookmakers.join( ',' ), oddsFormat ] );

    const events = preview && preview.events ? preview.events.slice( 0, maxEvents ) : [];

    return wp.element.createElement(
      'div',
      blockProps,
      wp.element.createElement(
        InspectorControls,
        null,
        wp.element.createElement(
          PanelBody,
          { title: __( 'Odds Settings', 'wp-odds-comparison' ), initialOpen: true },
          wp.element.createElement( TextControl, {
            label: __( 'Block Title', 'wp-odds-comparison' ),
            value: title,
            onChange: ( v ) => setAttributes( { title: v } ),
          } ),
          wp.element.createElement( TextControl, {
            label: __( 'Sport Key', 'wp-odds-comparison' ),
            help: __( 'e.g. soccer_epl', 'wp-odds-comparison' ),
            value: sport,
            onChange: ( v ) => setAttributes( { sport: v } ),
          } ),
          wp.element.createElement( SelectControl, {
            label: __( 'Odds Format', 'wp-odds-comparison' ),
            value: oddsFormat,
            options: FORMATS,
            onChange: ( v ) => setAttributes( { oddsFormat: v } ),
          } ),
          wp.element.createElement( RangeControl, {
            label: __( 'Max Events', 'wp-odds-comparison' ),
            value: maxEvents,
            onChange: ( v ) => setAttributes( { maxEvents: v } ),
            min: 1,
            max: 20,
          } )
        ),
        wp.element.createElement(
          PanelBody,
          { title: __( 'Markets', 'wp-odds-comparison' ), initialOpen: false },
          marketOptions.map( ( m ) =>
            wp.element.createElement( CheckboxControl, {
              key: m.value,
              label: m.label,
              checked: markets.includes( m.value ),
              onChange: ( checked ) => toggleMarket( m.value, checked ),
            } )
          )
        ),
        wp.element.createElement(
          PanelBody,
          { title: __( 'Bookmakers', 'wp-odds-comparison' ), initialOpen: true },
          catalog.map( ( bm ) =>
            wp.element.createElement( CheckboxControl, {
              key: bm.value,
              label: bm.label,
              checked: bookmakers.includes( bm.value ),
              onChange: ( checked ) => toggleBookmaker( bm.value, checked ),
            } )
          )
        )
      ),
      title &&
        wp.element.createElement( 'h3', { className: 'wpoc-title' }, title ),
      ! bookmakers.length &&
        wp.element.createElement(
          Notice,
          { status: 'warning', isDismissible: false },
          __( 'Select at least one bookmaker in the sidebar.', 'wp-odds-comparison' )
        ),
      loading && wp.element.createElement( Spinner, null ),
      error &&
        wp.element.createElement(
          Notice,
          { status: 'error', isDismissible: false },
          error
        ),
      ! loading &&
        ! error &&
        events.map( ( event ) =>
          wp.element.createElement(
            'div',
            { key: event.id, className: 'wpoc-event wpoc-event--preview' },
            wp.element.createElement(
              'h4',
              null,
              event.home_team,
              ' vs ',
              event.away_team
            ),
            wp.element.createElement(
              'p',
              { className: 'wpoc-preview-meta' },
              Object.keys( event.bookmakers || {} ).length,
              ' ',
              __( 'bookmakers', 'wp-odds-comparison' )
            )
          )
        )
    );
  }

  registerBlockType( 'wp-odds-comparison/odds-comparison', {
    edit: Edit,
    save: () => null,
  } );
} )( window.wp );
