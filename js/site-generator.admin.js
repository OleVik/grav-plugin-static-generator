function staticGeneratorUpdateProgress(progress, total, message) {
  const toastMessage = document.querySelector(".toast-message");
  const status = `[${progress}/${total}] ${message}`;
  console.debug(status);
  toastMessage.textContent = status;
}

function staticGeneratorEventHandler(
  task,
  url,
  button,
  Toastr,
  StateColors,
  styleProperty = "color"
) {
  button.style[styleProperty] = StateColors.waiting;
  let EventHandler = new window.EventSource(url);
  const persist = {
    timeOut: 0,
    extendedTimeOut: 0
  };
  Toastr.info(StaticGeneratorTranslation.ADMIN.INDEX.WAITING, null, persist);
  button.disabled = true;
  EventHandler.addEventListener("open", event => {
    console.debug(`Executing task ${task}: ${url}`);
    Toastr.clear();
    Toastr.info(
      "[0/0]",
      StaticGeneratorTranslation.ADMIN.INDEX.ONGOING,
      persist
    );
  });
  EventHandler.addEventListener("error", event => {
    console.error(`Failed to execute task ${task}`, event);
    button.style[styleProperty] = StateColors.error;
    button.disabled = false;
    Toastr.error(StaticGeneratorTranslation.ADMIN.INDEX.ERROR, null, persist);
    EventHandler.close();
    setTimeout(function() {
      Toastr.clear();
    }, 2500);
  });
  var total = 0;
  EventHandler.addEventListener("message", event => {
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
        button.style[styleProperty] = StateColors.success;
        Toastr.success(
          data.content,
          StaticGeneratorTranslation.ADMIN.INDEX.SUCCESS,
          persist
        );
        if (data.text && data.value) {
          staticGeneratorUpdateSelectField(data.text, data.value);
        }
        setTimeout(function() {
          button.style.removeProperty(styleProperty);
          button.disabled = false;
          Toastr.clear();
        }, 5000);
      }
    } else {
      EventHandler.close();
      console.debug(`Executed task ${task}`);
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
    const staticGeneratorStateColors = {
      waiting: "#df8a13",
      error: "#b52b27",
      success: "#3d8b3d"
    };
    const staticGeneratorIndexButton = document.querySelector(
      ".static-generator-search-index"
    );
    const staticGeneratorPageRoute = encodeURIComponent(GravAdmin.config.route);
    if (staticGeneratorIndexButton) {
      const staticGeneratorPageRoute = encodeURIComponent(
        GravAdmin.config.route
      );
      const staticGeneratorIndexSearchRoute =
        GravAdmin.config.base_url_relative +
        ".json/task" +
        GravAdmin.config.param_sep +
        "indexSearch/admin-nonce" +
        GravAdmin.config.param_sep +
        GravAdmin.config.admin_nonce +
        "?mode=content" +
        "&route=" +
        staticGeneratorPageRoute;
      staticGeneratorIndexButton.addEventListener(
        "click",
        function(event) {
          staticGeneratorEventHandler(
            "indexSearch",
            staticGeneratorIndexSearchRoute,
            staticGeneratorIndexButton,
            Grav.default.Utils.toastr,
            staticGeneratorStateColors
          );
          event.preventDefault();
        },
        false
      );
    }
    const staticGeneratorPresets = document.querySelectorAll(
      "#blueprints ul.static-generator-presets li"
    );
    if (staticGeneratorPresets) {
      const staticGeneratorTaskRoute = function(task) {
        return (
          GravAdmin.config.base_url_relative +
          ".json/task" +
          GravAdmin.config.param_sep +
          task +
          "/admin-nonce" +
          GravAdmin.config.param_sep +
          GravAdmin.config.admin_nonce
        );
      };
      for (var preset of staticGeneratorPresets) {
        const copyButton = preset.querySelector(
          "a.static-generator-copy-preset"
        );
        const generateButton = preset.querySelector(
          "a.static-generator-preset-generate"
        );
        if (copyButton) {
          copyButton.addEventListener(
            "click",
            function(event) {
              const name = preset.querySelector('input[name$="[name]"]').value;
              console.log(
                staticGeneratorTaskRoute("copyPreset") + "?preset=" + name
              );
              staticGeneratorEventHandler(
                "copyPreset",
                staticGeneratorTaskRoute("copyPreset") + "?preset=" + name,
                copyButton,
                Grav.default.Utils.toastr,
                staticGeneratorStateColors,
                "background"
              );
              event.preventDefault();
            },
            false
          );
        }
        if (generateButton) {
          generateButton.addEventListener(
            "click",
            function(event) {
              const name = preset.querySelector('input[name$="[name]"]').value;
              console.log(
                event,
                name,
                staticGeneratorTaskRoute("generateFromPreset") +
                  "?preset=" +
                  name
              );
              staticGeneratorEventHandler(
                "generateFromPreset",
                staticGeneratorTaskRoute("generateFromPreset") +
                  "?preset=" +
                  name,
                generateButton,
                Grav.default.Utils.toastr,
                staticGeneratorStateColors,
                "background"
              );
              event.preventDefault();
            },
            false
          );
        }
      }
    }
  },
  false
);
