"use strict";

/**
 * ask browsers to stop with the autocorrupt already
 */
(function () {
  var fields = document.querySelectorAll("input[type='text'],input[type='password'],textarea");

  for (var i = 0, len = fields.length; i < len; i++) {
    var field = fields[i];
    field.autocorrect = field.type === "password" ? "new-password" : "off";
    field.autocapitalize = "off";
    field.spellcheck = false;
  }
})();
