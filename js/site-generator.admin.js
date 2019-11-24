function staticGeneratorUpdateProgress(progress, total, message) {
  const toastMessage = document.querySelector(".toast-message");
  const status = `[${progress}/${total}] ${message}`;
  toastMessage.textContent = status;
}

function staticGeneratorStore(url, indexButton, StateColors, Toastr) {
  let indexEvent = new window.EventSource(url);
  const persist = {
    timeOut: 0,
    extendedTimeOut: 0
  };
  Toastr.info(StaticGeneratorTranslation.ADMIN.INDEX.WAITING, null, persist);
  indexButton.disabled = true;
  indexEvent.addEventListener("open", event => {
    console.debug(`Executing task indexSearch: ${url}`);
    Toastr.clear();
    Toastr.info(
      "[0/0]",
      StaticGeneratorTranslation.ADMIN.INDEX.ONGOING,
      persist
    );
  });
  indexEvent.addEventListener("error", event => {
    console.error("Failed to execute task indexSearch", event);
    indexButton.style.color = StateColors.error;
    indexButton.disabled = false;
    Toastr.error(StaticGeneratorTranslation.ADMIN.INDEX.ERROR, null, persist);
    indexEvent.close();
    setTimeout(function() {
      Toastr.clear();
    }, 2500);
  });
  var total = 0;
  indexEvent.addEventListener("message", event => {
    const data = JSON.parse(event.data);
    if (data.total) {
      total = data.total;
    }
    if (data.content != "END-OF-STREAM") {
      if (data.progress) {
        staticGeneratorUpdateProgress(data.progress, total, data.content);
      }
      if (!data.progress && !data.total) {
        Toastr.clear();
        indexButton.style.color = StateColors.success;
        Toastr.success(
          data.content,
          StaticGeneratorTranslation.ADMIN.INDEX.SUCCESS,
          persist
        );
        if (data.text && data.value) {
          staticGeneratorUpdateSelectField(data.text, data.value);
        }
        setTimeout(function() {
          indexButton.style.removeProperty("color");
          indexButton.disabled = false;
          Toastr.clear();
        }, 5000);
      }
    } else {
      indexEvent.close();
      console.debug(`Executed task indexSearch: ${total} items stored`);
    }
  });
}

function staticGeneratorUpdateSelectField(text, value) {
  const staticGeneratorSelectField = document.querySelector(
    "#static-generator-search-files-select"
  );
  if (staticGeneratorSelectField) {
    staticGeneratorSelectField.selectize.addOption({
      text: text,
      value: value
    });
    staticGeneratorSelectField.selectize.setValue(value);
  }
}

window.addEventListener(
  "load",
  function(event) {
    const staticGeneratorIndexButton = document.querySelector(
      ".grav-plugin-static-generator-search-index"
    );
    if (staticGeneratorIndexButton) {
      const staticGeneratorPageRoute = encodeURIComponent(
        GravAdmin.config.route
      );
      const staticGeneratorindexSearchRoute =
        GravAdmin.config.base_url_relative +
        ".json/task" +
        GravAdmin.config.param_sep +
        "indexSearch/admin-nonce" +
        GravAdmin.config.param_sep +
        GravAdmin.config.admin_nonce +
        "?mode=content" +
        "&route=" +
        staticGeneratorPageRoute;
      const staticGeneratorStateColors = {
        waiting: "#df8a13",
        error: "#b52b27",
        success: "#3d8b3d"
      };
      staticGeneratorIndexButton.addEventListener(
        "click",
        function(event) {
          staticGeneratorIndexButton.style.color =
            staticGeneratorStateColors.waiting;
          staticGeneratorStore(
            staticGeneratorindexSearchRoute,
            staticGeneratorIndexButton,
            staticGeneratorStateColors,
            Grav.default.Utils.toastr
          );
          event.preventDefault();
        },
        false
      );
    }
  },
  false
);
