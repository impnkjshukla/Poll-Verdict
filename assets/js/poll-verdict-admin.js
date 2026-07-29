( function( $ ) {
	'use strict';

	$( document ).on( 'click', '#pv-add-row', function( e ) {
		e.preventDefault();
		var tpl = document.getElementById( 'pv-row-template' );
		var row = tpl.content ? tpl.content.firstElementChild.cloneNode( true ) : null;
		if ( ! row ) return;
		$( '#pv-options-table tbody' ).append( row );
	} );

	$( document ).on( 'click', '.pv-remove-row', function( e ) {
		e.preventDefault();
		var $rows = $( '#pv-options-table tbody tr' );
		if ( $rows.length <= 2 ) {
			alert( 'A poll needs at least two options.' );
			return;
		}
		$( this ).closest( 'tr' ).remove();
	} );

	/* -------- Reusable WP media uploader for image fields -------- */
	/* Used by: Poll Image (per-poll metabox) and Fallback Poll Image (Settings page). */

	var pvMediaFrame = null;

	$( document ).on( 'click', '.pv-media-upload', function( e ) {
		e.preventDefault();
		if ( typeof wp === 'undefined' || ! wp.media ) return;

		var $btn        = $( this );
		var $wrap       = $btn.closest( '.pv-image-field-wrap' );
		var $input      = $( '#' + $btn.data( 'target' ) );
		var $preview    = $( '#' + $btn.data( 'preview' ) );
		var $removeBtn  = $wrap.find( '.pv-media-remove' );

		pvMediaFrame = wp.media( {
			title: 'Select or Upload Image',
			button: { text: 'Use this image' },
			multiple: false
		} );

		pvMediaFrame.on( 'select', function () {
			var attachment = pvMediaFrame.state().get( 'selection' ).first().toJSON();
			var url = ( attachment.sizes && attachment.sizes.medium ) ? attachment.sizes.medium.url : attachment.url;
			$input.val( attachment.id );
			$preview.attr( 'src', url ).show();
			$removeBtn.show();
		} );

		pvMediaFrame.open();
	} );

	$( document ).on( 'click', '.pv-media-remove', function( e ) {
		e.preventDefault();
		var $btn     = $( this );
		var $wrap    = $btn.closest( '.pv-image-field-wrap' );
		var $input   = $( '#' + $btn.data( 'target' ) );
		var $preview = $( '#' + $btn.data( 'preview' ) );

		$input.val( '' );
		$preview.hide().attr( 'src', '' );
		$btn.hide();
	} );

} )( jQuery );
