(function ($, Drupal) {

  function checkTime(i) {
    return (i < 10) ? "0" + i : i;
  }

  function getTime() {
    var today = new Date(),
        h = checkTime(today.getHours()),
        m = checkTime(today.getMinutes());
    return h + ":" + m;

  }

  function startTime() {
    setTimeout(function () {
      startTime()
    }, 500);

    var time = getTime();
    document.getElementById('time').innerHTML =  Drupal.t("Don't miss your train! It's @time.",{'@time': time});
  }

  Drupal.behaviors.timeCheck = {
    attach: function (context) {
      startTime();
    }
  };
})(jQuery, Drupal);