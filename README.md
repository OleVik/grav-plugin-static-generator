# Static Generator Plugin

The **Static Generator** Plugin is made for the [Grav CMS](http://github.com/getgrav/grav), and facilitates indexing and static generation of Pages.

## Configuration

Before configuring the plugin, you should copy the `user/plugins/static-generator/static-generator.yaml` to `user/config/plugins/static-generator.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
# Enable or disable the plugin
enabled: true
# Where to store indices
index: "user://data/persist"
# Where to store static content
content: "user://data/persist/static"
# Maximum character count in each Page
content_max_length: 100000
# Permission-levels that can see buttons in plugin options
content_permissions:
  - admin.super
  - admin.maintenance
# Enable in Admin-plugin
admin: true
# Use plugin's JavaScript in Admin-plugin
js: true
# Enable index-button in Admin Quick Tray
quick_tray: true
# Permission-levels that can see index-button in Admin Quick Tray
quick_tray_permissions:
  - admin.super
  - admin.maintenance
# Defined sets of presets
presets:
  - name: default
```

Note that if you use the Admin plugin, a file with your configuration named `static-generator.yaml` will be saved in the `user/config/plugins/` folder once changed and saved in the Admin.

## Usage

The Static Generator Plugin does two things: Index Page(s) metadata and content, and create static versions of Page(s).

### Indexing

If you're using the Admin plugin, two icons will be available in the quick navigation tray: A bolt and an archive-box. When clicked, the plugin will start indexing the content in `/user/pages`, and each Page will have an entry. By default, all content is indexed, resulting in something like the code below.

**The bolt stores index data, which does not include the Page(s) content, whilst the archive-box does.** The time to do this for the plugin is negligible, but if you're loading this data for searching in your theme, know that loading and searching content is more resource-intensive.

```json
[
  {
    "title": "Body & Hero Classes",
    "date": "2017-08-11T12:55:00+00:00",
    "url": "http://localhost:8000/blog/hero-classes",
    "taxonomy": {
      "categories": [
        "blog"
      ],
      "tags": []
    },
    "media": [
      "unsplash-overcast-mountains.jpg"
    ],
    "content": "The [Quark theme](https://getgrav.org/downloads/themes) ...\n"
  },
  {
    "title": "Text & Typography",
    "date": "2017-07-19T11:34:00+00:00",
    "url": "http://localhost:8000/blog/text-typography",
    "taxonomy": {
      "categories": [
        "blog"
      ],
      "tags": []
    },
    "media": [
      "unsplash-text.jpg"
    ],
    "content": "The [Quark theme](https://github.com/getgrav/grav-theme-quark) ...\n"
  }
]
```

It will be wrapped for use in JavaScript, like `const GravDataIndex = [...];`. This makes it apt for use with search engines, like [FlexSearch](https://github.com/nextapps-de/flexsearch/). You can include the resulting `.js`-file and use `GravDataIndex` for searching Pages. If viewing a specific Page in Admin, for example at `http://localhost:8000/en/admin/pages/blog`, it will index the descendants of this Page in a specific-file named `blog.full.js`.

The same can be achieved through the command-line, with the command `php bin/plugin static-generator index`. **See `php bin/plugin static-generator help index` for options**, a normal `php bin/plugin static-generator index "/" -c` will index all Pages including content.

#### Usage

```bash
php bin/plugin static-generator index [options] [--] <route> [<target>]
```

#### Arguments

```bash
route  The route to the page
target Override target-option or set a custom destination
```

#### Available options

```bash
-b, --basename[=BASENAME]  Index basename [default: "index"]
-c, --content              Include Page content
-e, --echo                 Outputs result directly
-w, --wrap                 Wraps JSON as a JavaScript global
-f, --force                Forcefully save data
-h, --help                 Display this help message
-q, --quiet                Do not output any message
-V, --version              Display this application version
    --ansi                 Force ANSI output
    --no-ansi              Disable ANSI output
-n, --no-interaction       Do not ask any interactive question
-v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Static Generation

If you want to generate static versions of the Page(s), use the `php bin/plugin static-generator page`-command. This will create a `index.html`-file for each Page, located in your preset `content`-location, like `/user/data/persist/static`. The folder-structure of `/user/pages` will remain intact, and assets output alongside, for example to `/user/data/persist/static/assets`. Asset-paths will be rewritten, also for media which will remain alongside each Page's `index.html`-file.

#### Usage

```bash
php bin/plugin static-generator page [options] [--] [<route> [<collection> [<target>]]]
```

#### Available options

```bash
route      The route to the page
collection The Page Collection to store (see https://learn.getgrav.org/16/content/collections#collection-headers)
target     Override target-option or set a custom destination
```

#### Available options

```bash
-p, --preset[=PRESET]          Name of Config preset
-a, --assets                   Include Assets
-r, --root-prefix=ROOT-PREFIX  Root prefix for assets and images
-s, --static-assets            Include Static Assets
-i, --images                   Include Images
-o, --offline                  Force offline-mode
-f, --filter[=FILTER]          Methods for filtering (multiple values allowed)
```

For example, `php bin/plugin static-generator page "@page.descendants" "/blog"` results in:

```
\---static
    +---assets
    |   \---user
    |       \---themes
    |           \---scholar
    |               +---css
    |               |   |   print.css
    |               |   |   theme.css
    |               |   |
    |               |   \---styles
    |               |           metal.css
    |               |
    |               +---js
    |               |       theme.js
    |               |
    |               \---node_modules
    |                   +---dayjs
    |                   |   |   dayjs.min.js
    |                   |   |
    |                   |   \---plugin
    |                   |           advancedFormat.js
    |                   |
    |                   \---flexsearch
    |                       \---dist
    |                               flexsearch.min.js
    |
    \---blog
        +---classic-modern-architecture
        |       index.html
        |       unsplash-luca-bravo.jpg
        |
        +---daring-fireball-link
        |       index.html
        |       refuge-des-merveilles-tende-france---denis-degioanni-unsplashcom.jpg
        |
        +---focus-and-blur
        |       index.html
        |       unsplash-focus.jpg
        |
        +---hero-classes
        |       index.html
        |       unsplash-overcast-mountains.jpg
        |
        +---london-at-night
        |       index.html
        |       unsplash-london-night.jpg
        |       unsplash-xbrunel-johnson.jpg
        |
        +---random-thoughts
        |       index.html
        |
        +---text-typography
        |       index.html
        |       unsplash-text.jpg
        |
        \---the-urban-jungle
                index.html
                unsplash-sidney-perry.jpg
```

### Cleanup

The `php bin/plugin static-generator clear` command will delete the preset folders containing indices and static content.

### Debugging

The `php bin/plugin static-generator test` command attempts to iterate Page(s) in the samme manner that the `index` and `page` commands do, to verify that the indices and static content can be generated.

## Installation

Installing the Static Generator plugin can be done in one of three ways: With the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) or with a zip-file.

### GPM

The simplest way to install the plugin is using the GPM through your system's terminal, also called the command line. From the root of your Grav-installation folder, type:

    bin/gpm install static-generator

This will install the plugin into the `/user/plugins`-directory within Grav. Its files can be found under `/your/grav/site/user/plugins/static-generator`.

### Manual

To install the plugin, download the zip-version of this repository and unzip it under `/your/grav/site/user/plugins`. Then rename the folder to `static-generator`. You can find these files on [GitHub](https://github.com/OleVik/grav-plugin-static-generator) or via [GetGrav.org](http://getgrav.org/downloads/plugins).

You should now have all the plugin files under

    /your/grav/site/user/plugins/static-generator

### Admin Plugin

If you use the Admin plugin, you can install it directly by browsing the `Plugins`-tab and clicking on the `Add` button.

## Credits

- Developed by [Ole Vik](https://github.com/OleVik)
- Version 2.0.0-refactor sponsored by [Paul Hibbitts](https://twitter.com/hibbittsdesign)

## TODO

- [ ] content_max_length not used on Collection?