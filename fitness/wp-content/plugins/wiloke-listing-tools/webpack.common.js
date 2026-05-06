// webpack.config.js
const path = require("path");
const VueLoaderPlugin = require("vue-loader/lib/plugin");
require("dotenv").config({ path: "./.env" });
const ASSET_URL = process.env.ASSET_URL;
// console.log(ASSET_URL);
const chunkFilename =
  process.env.MODE === "dev" ? "[name].[id].js" : "[name].[id].js";

module.exports = {
  watch: true,
  // node: {
  //     fs: 'empty'
  // },
  entry: {
    general: "./admin/source/dev/general.js",
    global: "./admin/source/dev/global.js",
    "listing-plan": "./admin/source/dev/listing-plan.js",
    "add-custom-posttype": "./admin/source/dev/add-custom-posttype.js",
    script: "./admin/source/dev/script.js",
    "listing-settings": "./admin/source/dev/listing-settings.js",
    'claim-script': './admin/source/dev/claim-script.js',
    // 'design-fields': './admin/source/dev/design-fields.js',
    "event-script": "./admin/source/dev/event-script.js",
    // 'design-single-nav': './admin/source/dev/design-single-nav.js',
    // 'design-sidebar': './admin/source/dev/design-sidebar.js',
    // 'design-highlight-boxes': './admin/source/dev/design-highlight-boxes.js',
    "promotion-script": "./admin/source/dev/promotion-script.js",
    // 'design-search-form': './admin/source/dev/design-search-form.js',
    // 'design-hero-search-form': './admin/source/dev/design-hero-search-form.js',
    "report-script": "./admin/source/dev/report-script.js",
    "quick-search-form-script":
      "./admin/source/dev/quick-search-form-script.js",
    "listing-tools": "./admin/source/dev/listing-tools.js",
    "mobile-menu": "./admin/source/dev/mobile-menu.js",
    "listing-card": "./admin/source/dev/listing-card.js",
    "schema-markup": "./admin/source/dev/schema-markup.js",
    "pw-select2": "./admin/source/dev/pw-select2.js",
    "verify-purchase-code": "./admin/source/dev/verify-purchase-code.js",
    "push-notifications": "./admin/source/dev/push-notifications.js",
    "import-export-wiloke-tools":
      "./admin/source/dev/import-export-wiloke-tools.js",
    "wiloke-submission-general":
      "./admin/source/dev/wiloke-submission-general.js",
    "plan-controller": "./admin/source/dev/plan-controller.js",
    contactform7: "./admin/source/dev/contactform7.js",
    noticeafterupdating: "./admin/source/dev/noticeafterupdating.js",
    "mailtpl-admin": "./admin/source/dev/mailtpl-admin.js",
    "select2-field": "./admin/source/dev/select2-field.js",
  },
  output: {
    filename: "[name].js",
    chunkFilename: chunkFilename,
    path: path.resolve(__dirname, "admin/source/js"),
    publicPath: ASSET_URL
  },
  resolve: {
    alias: {
      vue$: "vue/dist/vue.esm.js"
    },
    extensions: ["*", ".js", ".vue", ".json"]
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: "vue-loader"
      },
      // this will apply to both plain `.js` files
      // AND `<script>` blocks in `.vue` files
      {
        test: /\.js$/,
        exclude: /node_modules/,
        loader: "babel-loader"
      },
      // this will apply to both plain `.css` files
      // AND `<style>` blocks in `.vue` files
      {
        test: /\.css$/,
        use: ["vue-style-loader", "css-loader"]
      }
    ]
  },
  plugins: [
    // make sure to include the plugin for the magic
    new VueLoaderPlugin()
  ],
  optimization: {
    splitChunks: {
      chunks: "async",
      cacheGroups: {
        // Cache Group
        vendors: {
          test: /[\/]node_modules[\/]/,
          priority: -10
        },
        default: {
          minChunks: 2,
          priority: -20,
          reuseExistingChunk: true
        }
      }
    }
  }
};
