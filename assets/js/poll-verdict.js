( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initCountdowns();
		initVoting();
		initCarousels();
	} );

	/* ---------------- Countdown timers ---------------- */

	function initCountdowns() {
		var cards = document.querySelectorAll( '.pv-poll-card[data-end]' );
		cards.forEach( function ( card ) {
			var end = parseInt( card.getAttribute( 'data-end' ), 10 );
			if ( ! end ) return;
			var box = card.querySelector( '[data-role="countdown"]' );
			if ( ! box ) return;

			var tick = function () {
				var diff = end - Date.now();
				if ( diff <= 0 ) {
					setUnit( box, 'days', '00' );
					setUnit( box, 'hours', '00' );
					setUnit( box, 'mins', '00' );
					setUnit( box, 'secs', '00' );
					clearInterval( timer );
					lockCard( card, ( window.PV_Data && PV_Data.i18n && PV_Data.i18n.closed ) || 'Voting Closed' );
					return;
				}
				var s = Math.floor( diff / 1000 );
				var days = Math.floor( s / 86400 );
				var hours = Math.floor( ( s % 86400 ) / 3600 );
				var mins = Math.floor( ( s % 3600 ) / 60 );
				var secs = s % 60;
				setUnit( box, 'days', pad( days ) );
				setUnit( box, 'hours', pad( hours ) );
				setUnit( box, 'mins', pad( mins ) );
				setUnit( box, 'secs', pad( secs ) );
			};
			tick();
			var timer = setInterval( tick, 1000 );
		} );
	}

	function pad( n ) { return ( n < 10 ? '0' : '' ) + n; }

	function setUnit( box, unit, val ) {
		var el = box.querySelector( '[data-unit="' + unit + '"]' );
		if ( el ) el.textContent = val;
	}

	function lockCard( card, message ) {
		card.querySelectorAll( '.pv-option-btn, .pv-vote-now-btn' ).forEach( function ( b ) {
			b.disabled = true;
		} );
		var msg = card.querySelector( '.pv-message' );
		if ( msg && ! msg.textContent ) {
			msg.textContent = message;
			msg.classList.add( 'pv-message-error' );
		}
	}

	/* ---------------- Voting ---------------- */

	function initVoting() {
		document.querySelectorAll( '.pv-poll-card' ).forEach( function ( card ) {
			var selectedIndex = null;
			var optionBtns = card.querySelectorAll( '.pv-option-btn' );
			var voteBtn = card.querySelector( '.pv-vote-now-btn' );
			var msg = card.querySelector( '.pv-message' );
			var alreadyVoted = card.getAttribute( 'data-voted' ) === '1';

			optionBtns.forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					if ( alreadyVoted ) return;
					optionBtns.forEach( function ( b ) { b.classList.remove( 'pv-selected' ); } );
					btn.classList.add( 'pv-selected' );
					selectedIndex = parseInt( btn.getAttribute( 'data-index' ), 10 );
				} );
			} );

			if ( ! voteBtn ) return;

			voteBtn.addEventListener( 'click', function () {
				if ( alreadyVoted ) return;
				if ( null === selectedIndex ) {
					if ( msg ) {
						msg.textContent = 'Please choose an option first.';
						msg.classList.add( 'pv-message-error' );
					}
					return;
				}
				castVote( card, selectedIndex, optionBtns, voteBtn, msg );
			} );
		} );
	}

	function castVote( card, index, optionBtns, voteBtn, msg ) {
		var pollId = card.getAttribute( 'data-poll-id' );
		voteBtn.disabled = true;

		var body = new URLSearchParams();
		body.append( 'action', 'pv_vote' );
		body.append( 'nonce', PV_Data.nonce );
		body.append( 'poll_id', pollId );
		body.append( 'option_index', index );

		fetch( PV_Data.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				var payload = res.data || {};
				if ( res.success ) {
					card.setAttribute( 'data-voted', '1' );
					optionBtns.forEach( function ( b ) { b.disabled = true; } );
					var btnText = voteBtn.querySelector( '.pv-vote-btn-text' );
					if ( btnText ) btnText.textContent = payload.already_voted ? 'Already Voted' : 'Vote Recorded';
					if ( msg ) {
						msg.classList.remove( 'pv-message-error' );
						msg.textContent = payload.message || 'Thanks for voting!';
					}
					if ( payload.options ) {
						updateResults( card, payload.options, payload.total || 0 );
					}
				} else {
					voteBtn.disabled = false;
					if ( msg ) {
						msg.classList.add( 'pv-message-error' );
						msg.textContent = payload.message || 'Something went wrong. Please try again.';
					}
					if ( payload.closed ) {
						lockCard( card, payload.message || 'Voting Closed' );
					}
					if ( payload.options ) {
						updateResults( card, payload.options, payload.total || 0 );
					}
				}
			} )
			.catch( function () {
				voteBtn.disabled = false;
				if ( msg ) {
					msg.classList.add( 'pv-message-error' );
					msg.textContent = 'Network error. Please try again.';
				}
			} );
	}

	function updateResults( card, options, total ) {
		[ '[data-role="results"]', '.pv-inline-results' ].forEach( function ( sel ) {
			var container = card.querySelector( sel );
			if ( ! container ) return;
			container.style.display = '';
			options.forEach( function ( opt, i ) {
				var row = container.querySelector( '.pv-result-row[data-index="' + i + '"]' );
				if ( ! row ) return;
				var percent = total > 0 ? Math.round( ( opt.votes / total ) * 100 ) : 0;
				var pctEl = row.querySelector( '[data-role="percent"]' );
				var votesEl = row.querySelector( '[data-role="votes"]' );
				var fillEl = row.querySelector( '.pv-bar-fill' );
				if ( pctEl ) pctEl.textContent = percent;
				if ( votesEl ) votesEl.textContent = opt.votes;
				if ( fillEl ) fillEl.style.width = percent + '%';
			} );
		} );
		var totalEl = card.querySelector( '[data-role="total-votes"]' );
		if ( totalEl ) totalEl.textContent = total;
	}

	/* ---------------- Carousel ---------------- */

	function initCarousels() {
		document.querySelectorAll( '.pv-carousel' ).forEach( function ( wrap ) {
			var track = wrap.querySelector( '[data-role="track"]' );
			var slides = wrap.querySelectorAll( '.pv-carousel-slide' );
			var dots = wrap.querySelectorAll( '.pv-dot' );
			var prev = wrap.querySelector( '.pv-prev' );
			var next = wrap.querySelector( '.pv-next' );
			var count = slides.length;
			var index = 0;
			var autoplay = wrap.getAttribute( 'data-autoplay' ) === '1';
			var interval = parseInt( wrap.getAttribute( 'data-interval' ), 10 ) || 6000;
			var timer = null;

			function goTo( i ) {
				index = ( i + count ) % count;
				track.style.transform = 'translateX(-' + ( index * 100 ) + '%)';
				dots.forEach( function ( d, di ) {
					d.classList.toggle( 'pv-dot-active', di === index );
				} );
			}

			if ( prev ) prev.addEventListener( 'click', function () { goTo( index - 1 ); restart(); } );
			if ( next ) next.addEventListener( 'click', function () { goTo( index + 1 ); restart(); } );
			dots.forEach( function ( d ) {
				d.addEventListener( 'click', function () {
					goTo( parseInt( d.getAttribute( 'data-slide' ), 10 ) );
					restart();
				} );
			} );

			// Basic touch swipe support.
			var startX = null;
			track.addEventListener( 'touchstart', function ( e ) { startX = e.touches[0].clientX; }, { passive: true } );
			track.addEventListener( 'touchend', function ( e ) {
				if ( null === startX ) return;
				var diff = e.changedTouches[0].clientX - startX;
				if ( Math.abs( diff ) > 40 ) {
					goTo( diff < 0 ? index + 1 : index - 1 );
					restart();
				}
				startX = null;
			} );

			function start() {
				if ( ! autoplay || count < 2 ) return;
				timer = setInterval( function () { goTo( index + 1 ); }, interval );
			}
			function stop() {
				if ( timer ) clearInterval( timer );
			}
			function restart() { stop(); start(); }

			wrap.addEventListener( 'mouseenter', stop );
			wrap.addEventListener( 'mouseleave', start );

			goTo( 0 );
			start();
		} );
	}

} )();
