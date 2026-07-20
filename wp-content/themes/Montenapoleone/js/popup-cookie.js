jQuery(document).ready(function () {
  jQuery(".close").click(function () {
    jQuery(".main-popup").fadeOut();
  });

  var visits = jQuery.cookie("visits") || 0;
  visits++;

  jQuery.cookie("visits", visits, { expires: 1, path: "/" });

  console.debug(jQuery.cookie("visits"));

  if (jQuery.cookie("visits") > 1) {
    jQuery(".main-popup").hide();
  } else {
    var pageHeight = jQuery(document).height();

    jQuery(".main-popup").show();
  }

  if (jQuery.cookie("noShowWelcome")) {
    jQuery(".main-popup").hide();
  }
});

jQuery(document).mouseup(function (e) {
  var container = jQuery(".main-popup");

  if (!container.is(e.target) && container.has(e.target).length === 0) {
    container.fadeOut();
  }
});
