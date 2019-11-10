async function store(url, indexButton) {
  let response = await fetch(url);
  if (response.ok) {
    console.log(response);
    indexButton.style.color = stateColors.success;
    setTimeout(function() {
      indexButton.style.removeProperty("color");
    }, 2500);
  } else {
    indexButton.style.color = stateColors.error;
    console.error("HTTP-Error: " + response.status);
  }
}

const stateColors = {
  waiting: "#df8a13",
  error: "#b52b27",
  success: "#3d8b3d"
};

window.addEventListener(
  "load",
  function(event) {
    var indexButton = document.querySelector(".search-index");
    if (indexButton) {
      indexButton.addEventListener(
        "click",
        function(event) {
          indexButton.style.color = stateColors.waiting;
          console.debug("Executing taskIndexSearch");
          store(
            GravAdmin.config.base_url_relative +
              ".json/task" +
              GravAdmin.config.param_sep +
              "taskIndexSearch",
            indexButton
          );
          event.preventDefault();
        },
        false
      );
    }
    console.log(
      GravAdmin.config.base_url_relative +
        ".json/task" +
        GravAdmin.config.param_sep +
        "taskIndexSearch"
    );
    console.log(GravAdmin.config.admin_nonce);
  },
  false
);
