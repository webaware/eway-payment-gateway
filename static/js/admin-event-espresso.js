/**
 * ask browsers to stop with the autocorrupt already
 */
(function () {
  var fields = document.querySelectorAll(".eway-no-autocorrupt");

  for (var i = 0, len = fields.length; i < len; i++) {
    var field = fields[i];
    field.autocorrect = field.type === "password" ? "new-password" : "off";
    field.autocapitalize = "off";
    field.spellcheck = false;
  }
})();
