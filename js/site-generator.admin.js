async function store(url, indexButton, StateColors, Toastr) {
  Toastr.info("Indexing Page ...");
  let response = await fetch(url);
  const reader = response.body.getReader();
  console.log(reader);
  const decoder = new TextDecoder("utf-8");
  reader.read().then(({ value, done }) => {
    console.log(decoder.decode(value));
    if (done) {
      console.log(done);
    }
  });

  if (response.ok) {
    console.log(response);
    // const json = await response.json();
    // console.log("Success:", JSON.stringify(json));
    Toastr.success("Indexed Page");
    indexButton.style.color = StateColors.success;
    setTimeout(function() {
      indexButton.style.removeProperty("color");
    }, 2500);
  } else {
    Toastr.error("Fail to index Page");
    indexButton.style.color = StateColors.error;
    console.error("HTTP-Error: " + response.status);
  }
}

window.addEventListener(
  "load",
  function(event) {
    var indexButton = document.querySelector(
      ".grav-plugin-static-generator-search-index"
    );
    if (indexButton) {
      const StateColors = {
        waiting: "#df8a13",
        error: "#b52b27",
        success: "#3d8b3d"
      };
      const Toastr = Grav.default.Utils.toastr;
      indexButton.addEventListener(
        "click",
        function(event) {
          indexButton.style.color = StateColors.waiting;
          console.debug("Executing task indexSearch");
          store(
            GravAdmin.config.base_url_relative +
              ".json/task" +
              GravAdmin.config.param_sep +
              "indexSearch/admin-nonce" +
              GravAdmin.config.param_sep +
              GravAdmin.config.admin_nonce,
            indexButton,
            StateColors,
            Toastr
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
        "indexSearch/admin-nonce" +
        GravAdmin.config.param_sep +
        GravAdmin.config.admin_nonce
    );
  },
  false
);
