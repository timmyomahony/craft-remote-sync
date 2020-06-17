(function ($) {
  Craft.RemoteSyncSettings = Garnish.Base.extend({
    init: function (formId) {
      this.$form = $("#" + formId);
    }
  });
})(jQuery);
