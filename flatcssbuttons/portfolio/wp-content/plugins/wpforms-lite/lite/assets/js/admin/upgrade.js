/* globals wpforms_admin, wpforms_upgrade */
/**
 * WPForms Settings functionality.
 *
 * @since 1.5.3.2
 */

'use strict';

var WPFormsUpgrade = window.WPFormsUpgrade || ( function( document, window, $ ) {

	/**
	 * Elements reference.
	 *
	 * @since 1.5.3.2
	 *
	 * @type {Object}
	 */
	var el = {
		$licenseKey:        $( '#wpforms-settings-upgrade-license-key' ),
		$licenseKeySpinner: $( '#wpforms-settings-upgrade-license-key ~ .wpforms-spinner' ),
		$licenseKeyCont:    $( '#wpforms-settings-upgrade-license-key-cont' ),
		$upgradeBtn:        $( '#wpforms-settings-upgrade-btn' ),
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.5.1
	 *
	 * @type {Object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.5.3.2
		 */
		init: function() {
			$( document ).ready( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.5.3.2
		 */
		ready: function() {
			app.displayForm();
			app.events();
		},

		/**
		 * Display license key form.
		 *
		 * @since 1.5.3.2
		 */
		displayForm: function() {
			var key = el.$licenseKey.val();

			if ( key === '' ) {
				el.$licenseKeyCont.removeClass( 'wpforms-hide' );
			} else {
				el.$upgradeBtn.removeClass( 'wpforms-hide' );
			}
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.5.3.2
		 */
		events: function() {
			app.licenseEvent();
			app.upgradeBtnClick();
		},

		/**
		 * Register license key input event.
		 *
		 * @since 1.5.3.2
		 */
		licenseEvent: function() {
			var timeout = null;

			el.$licenseKey.on( 'input', function() {
				if ( timeout !== null ) {
					clearTimeout( timeout );
				}
				timeout = setTimeout( function() {
					app.licenseVerify();
				}, 500 );
			} );
		},

		/**
		 * Register upgrade button event.
		 *
		 * @since 1.5.3.2
		 */
		upgradeBtnClick: function() {
			el.$upgradeBtn.on( 'click', function() {
				$.alert( app.licenseVerifySuccess() );
			} );
		},

		/**
		 * Verify a license key.
		 *
		 * @since 1.5.3.2
		 */
		licenseVerify: function() {
			var data = {
					action: 'wpforms_verify_license',
					nonce:   wpforms_admin.nonce,
					license: el.$licenseKey.val(),
				};
			if ( data.license === '' ) {
				return;
			}
			el.$licenseKeySpinner.removeClass( 'wpforms-hide' );
			$.post( wpforms_admin.ajax_url, data )
				.done( function( res ) {
					var alertArgs;
					if ( res.success ) {
						alertArgs = app.licenseVerifySuccess( res );
					} else {
						alertArgs = app.licenseVerifyError( res );
					}
					$.alert( alertArgs );
				} )
				.fail( function( xhr ) {
					app.failAlert( xhr );
				} )
				.always( function( xhr ) {
					el.$licenseKeySpinner.addClass( 'wpforms-hide' );
				} );
		},

		/**
		 * Get the alert arguments in case of success.
		 *
		 * @since 1.5.3.2
		 *
		 * @param {object} res Ajax query result object.
		 *
		 * @returns {object} Alert arguments.
		 */
		licenseVerifySuccess: function( res ) {
			var buttons = {
				confirm: {
					text: wpforms_upgrade.upgrd_to_pro_btn_upgrade,
					btnClass: 'btn-confirm',
					keys: [ 'enter' ],
					action: function() {
						app.gotoUpgradeUrl();
					},
				},
				cancel: {
					text: wpforms_upgrade.upgrd_to_pro_btn_cancel,
					keys: [ 'esc' ],
				},
			};
			return {
				title: wpforms_upgrade.upgrd_to_pro_license_ok_title,
				content: wpforms_upgrade.upgrd_to_pro_license_ok_msg,
				icon: 'fa fa-check-circle',
				type: 'green',
				buttons: buttons,
			};
		},

		/**
		 * Get the alert arguments in case of error.
		 *
		 * @since 1.5.3.2
		 *
		 * @param {object} res Ajax query result object.
		 *
		 * @returns {object} Alert arguments.
		 */
		licenseVerifyError: function( res ) {
			return {
				title: wpforms_upgrade.error,
				content: res.data,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_admin.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			};
		},

		/**
		 * Get the alert arguments in case of Pro already installed.
		 *
		 * @since 1.5.3.2
		 *
		 * @param {object} res Ajax query result object.
		 *
		 * @returns {object} Alert arguments.
		 */
		proAlreadyInstalled: function( res ) {
			var buttons = {
				confirm: {
					text: wpforms_upgrade.upgrd_to_pro_btn_activate,
					btnClass: 'btn-confirm',
					keys: [ 'enter' ],
					action: function() {
						window.location = window.location;
					},
				},
			};

			return {
				title: wpforms_upgrade.upgrd_to_pro_license_ok_title,
				content: res.data.message,
				icon: 'fa fa-check-circle',
				type: 'green',
				buttons: buttons,
			};
		},

		/**
		 * Go to upgrade url.
		 *
		 * @since 1.5.3.2
		 */
		gotoUpgradeUrl: function() {
			var data = {
				action: 'wpforms_get_upgrade_url',
				nonce:   wpforms_admin.nonce,
			};
			$.post( wpforms_admin.ajax_url, data )
				.done( function( res ) {
					if ( res.success ) {
						if ( res.data.reload ) {
							$.alert( app.proAlreadyInstalled( res ) );
							return;
						}
						window.location.href = res.data.url;
						return;
					}
					$.alert( {
						title: wpforms_upgrade.error,
						content: res.data.message,
						icon: 'fa fa-exclamation-circle',
						type: 'orange',
						buttons: {
							confirm: {
								text: wpforms_admin.ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
							},
						},
					} );
				} )
				.fail( function( xhr ) {
					app.failAlert( xhr );
				} );
		},

		/**
		 * Alert in case of server error.
		 *
		 * @since 1.5.3.2
		 *
		 * @param {object} xhr XHR object.
		 */
		failAlert: function( xhr ) {
			console.log( xhr );
			$.alert( {
				title: wpforms_upgrade.error,
				content: wpforms_upgrade.error_intro + ' ' + xhr.status + ' ' + xhr.statusText + ' ' + xhr.responseText,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_admin.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsUpgrade.init();
