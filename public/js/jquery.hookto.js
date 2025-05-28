/*!
 * Hook To - jQuery plugin
 * Move an element to another
 * with responsive back-in-place
 * @name jquery.hookto.js
 * @author Guillaume Bouillon
 * @see {@link https://github.com/Sade/jquery.hookTo|GitHub}
 * @usage (JS style) $('#element').hookTo('#hook-destination', {option_object, ...});
 * @usage (DOM style) <element data-hook-to="#hook-destination" data-hook-to-return="pixel_value">...</element>
 * 
 * updated to be jquery 3.6.0 compatible
 */
(function ($) {
  /**
   * The jQuery plugin namespace.
   * @external "jQuery.fn"
   * @see {@link http://docs.jquery.com/Plugins/Authoring The jQuery Plugin Guide}
   */

  /**
   * Hook To - jQuery plugin
   * @function external:"jQuery.fn".hookTo
   * @param destinationHook
   * @param {Object} options
   * @return {Array|Object|string|*}
   */
  $.fn.hookTo = function (destinationHook, options) {

    var _plugin = this;
    var settings = $.extend({
      'hookOriginPrefix': 'hookto-orig',
      'position': 'after',
      'returnAt': 768,
      'mobileFirst': true,
      'onInit': function () {},
      'onHook': function () {},
      'onUnhook': function () {}
    }, options);

    /**
     * Initialization of the plugin
     * @callback onInit
     * @param el
     */
    _plugin.init = function (el) {
      var $element = $(el);
      var origHash = settings.hookOriginPrefix + '-' + _plugin.getHash();
      var $origHook = $('<meta />').addClass('hookto-orig').attr({'id': origHash});

      $element.data('hookedTo', origHash);

      $origHook.insertBefore($element);
      _plugin.setPosition($element, settings.position);

      $(window).on('resize', function () {
        var condition = settings.mobileFirst ? $(window).outerWidth(true) <= settings.returnAt : $(window).outerWidth(true) >= settings.returnAt;

        if (condition) {
          _plugin.retrievePosition($element);
        } else {
          _plugin.setPosition($element, settings.position);
        }

      }).trigger('resize');

      settings.onInit.call($element);
    };

    /**
     * The element is hooked to its destination hook
     * @callback onHook
     * @param {Object} el
     * @param {String} position
     */
    _plugin.setPosition = function (el, position) {
      switch (position) {
        case 'default':
        case 'after':
          el.insertAfter(destinationHook);
          break;
        case 'before':
          el.insertBefore(destinationHook);
          break;
        case 'inside':
          el.appendTo(destinationHook);
          break;
      }

      settings.onHook.call(el);
    };

    /**
     * The hooked element returns to its previous position
     * @callback onUnhook
     * @param {Object} el
     */
    _plugin.retrievePosition = function (el) {
      var destination = $('#' + el.data('hookedTo'));
      if (destination.length && destination[0] !== el.prev()[0]) {
        destination.after(el);
      }

      settings.onUnhook.call(el);
    };

    /**
     * Get Hashed string name
     * @return {String}
     */
    _plugin.getHash = function () {
      var random = "";
      var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
      for (var i = 0; i < 12; i++) {
        random += possible.charAt(Math.floor(Math.random() * possible.length));
      }

      return random;
    };

    /**
     * Loop over all elements
     */
    return this.each(function () {
      _plugin.init(this);
    });
  };

  /**
   * Automatically apply Hook To functionality for elements with data attributes
   */
  $('[data-hook-to]').each(function () {
    var hook = $(this).data('hookTo');
    var options = {};
    
    if ($(this).data('hookTo-return')) {
      options.returnAt = $(this).data('hookTo-return');
    }
    if ($(this).data('hookTo-position')) {
      options.position = $(this).data('hookTo-position');
    }
    if ($(this).data('hookTo-mobileFirst') !== '') {
      options.mobileFirst = $(this).data('hookTo-mobileFirst');
    }
    if ($(this).data('hookTo-hookOriginPrefix')) {
      options.hookOriginPrefix = $(this).data('hookTo-hookOriginPrefix');
    }

    $(this).hookTo(hook, options);
  });

})(jQuery);
