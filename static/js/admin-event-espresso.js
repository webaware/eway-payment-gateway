/**
 * ask browsers to stop with the autocorrupt already
 */
(function () {
  const fields = document.querySelectorAll(".eway-no-autocorrupt");
  for (let i = 0, len = fields.length; i < len; i++) {
    const field = fields[i];
    field.autocorrect = field.type === "password" ? "new-password" : "off";
    field.autocapitalize = "off";
    field.spellcheck = false;
  }
})();
