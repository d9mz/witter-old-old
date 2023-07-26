/**
 * If you don't care about primitives and only objects then this function
 * is for you, otherwise look elsewhere.
 * This function will return `false` for any valid json primitive.
 * EG, 'true' -> false
 *     '123' -> false
 *     'null' -> false
 *     '"I'm a string"' -> false
 */
function tryParseJSONObject (jsonString){
  try {
      var o = JSON.parse(jsonString);

      // Handle non-exception-throwing cases:
      // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
      // but... JSON.parse(null) returns null, and typeof null === "object", 
      // so we must check for that, too. Thankfully, null is falsey, so this suffices:
      if (o && typeof o === "object") {
          return o;
      }
  }
  catch (e) { }

  return false;
};

// this REALLY stinks

let page = 1;
let keepLoading = true;
let cooldownActive = false;

$(window).scroll(function() {
  if($(window).scrollTop() + $(window).height() == $(document).height()) {
    if(keepLoading && !cooldownActive) {
      $.ajax({
        url: "/v1/api/load_weets/" + page,
        type: 'GET',
        tryCount: 0,
        retryLimit: 3,
        success: function(data) {
          $(".loading-dynamic").fadeIn();
          if (tryParseJSONObject(data)) {
            let json = JSON.parse(data);

            if (json.response == "cooldown") {
              // retrying
              this.tryCount++;
              cooldownActive = true; 
              if (this.tryCount <= this.retryLimit) {
                console.log("cooldown triggered, retrying after a delay...");
                let ajaxRequest = this;
                setTimeout(function() {
                  $.ajax(ajaxRequest);
                  cooldownActive = false; 
                }, 1000);
                return;
              } else {
                $(".loading-dynamic").fadeOut();
                console.log("exceeded retry limit.");
                keepLoading = false;
                cooldownActive = false;

                createAlert(2, "Exceeded infinite scroll retry limit! Please refresh your page to load more.");
              }
            } else {
              $(".loading-dynamic").fadeOut();
              keepLoading = false;
            }
          }

          if (keepLoading && !tryParseJSONObject(data)) {
            $(".loading-dynamic").fadeOut();
            $(".weet-container").append(data).fadeIn(1000);
            page++;
          }
        },
        error: function(xhr, textStatus, errorThrown) {
          createAlert(2, "An unknown error occured while trying to fetch more weets.");
          console.log("error occurred: " + textStatus);
        }
      });
    }
  }
});
