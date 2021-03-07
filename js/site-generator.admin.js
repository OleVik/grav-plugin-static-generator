/**
 * Throttle the rate at which a function can execute
 * @param {function} callback Function to execute
 * @param {number} interval Fire-rate limit in milliseconds
 */
function throttle(callback, interval) {
  let enableCall = true;
  return function (...args) {
    if (!enableCall) return;
    enableCall = false;
    callback.apply(this, args);
    setTimeout(() => (enableCall = true), interval);
  };
}

/**
 * Limit the rate at which a function can execute
 * @param {function} func Function to execute
 * @param {number} wait Fire-rate limit in milliseconds
 * @param {boolean} immediate Execute immediately
 * @see https://davidwalsh.name/javascript-debounce-function
 */
function debounce(func, wait, immediate) {
  var timeout;
  return function () {
    var context = this,
      args = arguments;
    var later = function () {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

/**
 * Update status-message
 * @param {number} progress i of n
 * @param {number} total n
 * @param {string} message Status
 */
function staticGeneratorUpdateProgress(progress, total, message) {
  const toastMessage = document.querySelector(".toast-message");
  const status = `[${progress}/${total}] ${message}`;
  console.debug(status);
  toastMessage.textContent = status;
}

/**
 *
 * @param {string} task Task to execute
 * @param {string} url URL for EventSource
 * @param {object} button DOM-element
 * @param {object} Toastr Toastr-instance
 * @param {object} StateColors Hash of colors
 * @param {string} styleProperty Property to stylize
 */
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
    extendedTimeOut: 0,
  };
  Toastr.info(StaticGeneratorTranslation.ADMIN.INDEX.WAITING, null, persist);
  button.disabled = true;
  EventHandler.addEventListener("open", (event) => {
    console.debug(`Executing task ${task}: ${url}`);
    Toastr.clear();
    Toastr.info(
      "[0/0]",
      StaticGeneratorTranslation.ADMIN.INDEX.ONGOING,
      persist
    );
  });
  EventHandler.addEventListener("error", (event) => {
    console.error(`Failed to execute task ${task}`, event);
    button.style[styleProperty] = StateColors.error;
    button.disabled = false;
    Toastr.error(StaticGeneratorTranslation.ADMIN.INDEX.ERROR, null, persist);
    EventHandler.close();
    setTimeout(function () {
      Toastr.clear();
    }, 2500);
  });
  var total = 0;
  EventHandler.addEventListener("message", (event) => {
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
        setTimeout(function () {
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

/**
 * Add and set option to select-field
 * @param {string} text Text for option
 * @param {string} value Value for option
 */
function staticGeneratorUpdateSelectField(text, value) {
  const staticGeneratorSelectField = document.querySelector(
    "#static-generator-search-files-select"
  );
  if (staticGeneratorSelectField) {
    staticGeneratorSelectField.selectize.addOption({
      text: text,
      value: value,
    });
    staticGeneratorSelectField.selectize.setValue(value);
  }
}

/**
 * Generate a unique identifier
 * @param {number} length Length of identifier
 */
function makeid(length) {
  var result = "";
  var characters =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  var charactersLength = characters.length;
  for (var i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }
  return result;
}

/**
 * Create CLI-code
 * @param {object} element DOM-element
 * @param {object} options Settings
 */
function setCode(element, options) {
  if (!options.hasOwnProperty("route")) {
    options.route = "";
  }
  let code = `php bin/plugin static-generator page "/${options.route}"`;
  if (options.hasOwnProperty("target") && options.target !== "") {
    code += ` "${options.target}"`;
  }
  if (options.hasOwnProperty("root_prefix") && options.root_prefix !== "") {
    code += ` -r "${options.root_prefix}"`;
  }
  if (options.hasOwnProperty("name") && options.name !== "") {
    code += ` -p "${options.name}"`;
  }
  if (options.hasOwnProperty("assets") && options.assets === true) {
    code += ` -a`;
  }
  if (
    options.hasOwnProperty("static_assets") &&
    options.static_assets === true
  ) {
    code += ` -s`;
  }
  if (options.hasOwnProperty("images") && options.images === true) {
    code += ` -i`;
  }
  if (options.hasOwnProperty("filters") && options.filters.length > 0) {
    options.filters.forEach((filter) => {
      code += ` -f "${filter}()"`;
    });
  }
  if (
    options.hasOwnProperty("parameters") &&
    Object.keys(options.parameters).length > 0
  ) {
    for (let [key, value] of Object.entries(options.parameters)) {
      code += ` -d "${key}:${value}"`;
    }
  }
  element.innerHTML = code;
}

/**
 * Respond to changes in fields
 * @param {object} root DOM-element
 */
function monitor(root) {
  const preElement = document.createElement("pre");
  preElement.classList.add("static-generator-command");
  preElement.setAttribute(
    "data-header",
    StaticGeneratorTranslation.ADMIN.GENERATE.COMMAND
  );
  root.appendChild(preElement);
  const codeElement = document.createElement("code");
  codeElement.setAttribute("id", makeid(16));
  preElement.appendChild(codeElement);
  const options = { parameters: {} };
  root
    .querySelector("[name*=filters]")
    .selectize.on("change", function (value) {
      options["filters"] = value.split(",");
      setCode(codeElement, options);
    });
  [
    root.querySelector("[name*=name]"),
    root.querySelector("[name*=route]"),
    root.querySelector("[name*=root_prefix]"),
    root.querySelector("[name*=target]"),
    root.querySelector("[name*=assets]"),
    root.querySelector("[name*=static_assets]"),
    root.querySelector("[name*=images]"),
  ].forEach((item) => {
    if (item !== null) {
      const name = item.name.match(/\[([^\]]*)\](?!.*])/i)[1];
      if (item.type == "checkbox") {
        options[name] = item.checked;
      } else {
        options[name] = item.value;
      }
      setCode(codeElement, options);
      item.addEventListener(
        "input",
        debounce(function (event) {
          if (event.srcElement.type == "checkbox") {
            options[name] = event.target.checked;
          } else {
            options[name] = event.target.value;
          }
          setCode(codeElement, options);
        }, 250)
      );
    }
  });
  [
    root.querySelector(
      "[data-grav-array-name*=parameters] [data-grav-array-type*=row] [data-grav-array-type*=key]"
    ),
    root.querySelector(
      "[data-grav-array-name*=parameters] [data-grav-array-type*=row] [data-grav-array-type*=value]"
    ),
  ].forEach((item) => {
    item.addEventListener(
      "input",
      debounce(function (event) {
        var key, value;
        if (event.target.dataset.gravArrayType == "key") {
          key = event.target.value;
          value = value = event.target.nextElementSibling.value;
        } else if (event.target.dataset.gravArrayType == "value") {
          key = event.target.previousElementSibling.value;
          value = event.target.value;
        }
        if (key !== "" && value !== "") {
          options["parameters"][key] = value;
        }
        setCode(codeElement, options);
      }, 250)
    );
  });
}

window.addEventListener(
  "load",
  function (event) {
    const staticGeneratorStateColors = {
      waiting: "#df8a13",
      error: "#b52b27",
      success: "#3d8b3d",
    };
    const staticGeneratorIndexButton = document.querySelector(
      ".static-generator-index"
    );
    const staticGeneratorContentButton = document.querySelector(
      ".static-generator-content"
    );
    const staticGeneratorPageRoute = encodeURIComponent(GravAdmin.config.route);
    if (staticGeneratorIndexButton) {
      let route =
        GravAdmin.config.base_url_relative +
        ".json/task" +
        GravAdmin.config.param_sep +
        "staticGeneratorIndex/admin-nonce" +
        GravAdmin.config.param_sep +
        GravAdmin.config.admin_nonce +
        "?mode=index" +
        "&route=" +
        staticGeneratorPageRoute;
      staticGeneratorIndexButton.addEventListener(
        "click",
        function (event) {
          staticGeneratorEventHandler(
            "staticGeneratorIndex",
            route,
            staticGeneratorIndexButton,
            Grav.default.Utils.toastr,
            staticGeneratorStateColors
          );
          event.preventDefault();
        },
        false
      );
    }
    if (staticGeneratorContentButton) {
      let route =
        GravAdmin.config.base_url_relative +
        ".json/task" +
        GravAdmin.config.param_sep +
        "staticGeneratorContent/admin-nonce" +
        GravAdmin.config.param_sep +
        GravAdmin.config.admin_nonce +
        "?mode=content" +
        "&route=" +
        staticGeneratorPageRoute;
      staticGeneratorContentButton.addEventListener(
        "click",
        function (event) {
          staticGeneratorEventHandler(
            "staticGeneratorContent",
            route,
            staticGeneratorContentButton,
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
      const staticGeneratorTaskRoute = function (task) {
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
            function (event) {
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
            function (event) {
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
    if (
      window.GravAdmin.config !== undefined &&
      window.GravAdmin.config.current_url !== undefined &&
      window.GravAdmin.config.current_url.includes("plugins/static-generator")
    ) {
      const wrappers = document.querySelectorAll(".form-tab");
      wrappers.forEach((element) => {
        if (element.querySelector('[data-grav-field="list"]')) {
          element
            .querySelectorAll('[data-grav-field="list"] [data-collection-item]')
            .forEach((item) => {
              monitor(item);
            });
        } else if (element.querySelector("[name*=route]")) {
          monitor(element);
        }
      });
    }
  },
  false
);
