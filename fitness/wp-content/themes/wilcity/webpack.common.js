// webpack.config.js
const webpack = require("webpack");
const path = require("path");
require("dotenv").config({ path: "./.env" });
const VueLoaderPlugin = require("vue-loader/lib/plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

const ASSET_URL = process.env.ASSET_URL;
const chunkFilename =
  process.env.NODE_ENV !== "production" ? "[id].js" : "[id].js";

module.exports = {
  target: "web",
  node: {
    fs: false
  },
  entry: {
    Optimization: "./assets/dev/js/Optimization.js",
    activeListItem: "./assets/dev/js/activeListItem.js",
    MagnificGalleryPopup: "./assets/dev/js/MagnificGalleryPopup.js",
    addlisting: "./assets/dev/js/addlisting.js",
    AddListingBtn: "./assets/dev/js/addlisting-btn.js",
    customLogin: "./assets/dev/js/customLogin.js",
    app: "./assets/dev/js/app.js",
    dashboard: "./assets/dev/js/dashboard.js",
    FavoriteStatistics: "./assets/dev/js/FavoriteStatistics.js",
    general: "./assets/dev/js/general.js",
    index: "./assets/dev/js/index.js",
    SearchFormV1: "./assets/dev/js/SearchFormV1.js",
    "quick-search": "./assets/dev/js/quick-search.js",
    resetPassword: "./assets/dev/js/resetPassword.js",
    shortcodes: "./assets/dev/js/shortcodes.js",
    "single-event": "./assets/dev/js/single-event.js",
    "single-listing": "./assets/dev/js/single-listing.js",
    WilokeDirectBankTransfer: "./assets/dev/js/WilokeDirectBankTransfer.js",
    WilokeGoogleMap: "./assets/dev/js/WilokeGoogleMap.js",
    WilokePayPal: "./assets/dev/js/WilokePayPal.js",
    WilokeSubmissionCouponCode: "./assets/dev/js/WilokeSubmitCouponCode.js",
    // addListingLog: './assets/dev/js/TestAddListingLog.js',
    proceedPayment: "./assets/dev/js/ProceedPayment.js",
    SearchFormV2: "./assets/dev/js/SearchFormV2.js",
    HeroSearchForm: "./assets/dev/js/HeroSearchForm.js",
    LoginRegister: "./assets/dev/js/LoginRegister.js",
    becomeAnAuthor: "./assets/dev/js/BecomeAnAuthor.js",
    PayAndPublish: "./assets/dev/js/PayAndPublish.js",
    MessageNotifications: "./assets/dev/js/MessageNotifications.js",
    Notification: "./assets/dev/js/Notification.js",
    author: "./assets/dev/js/Author.js",
    Follow: "./assets/dev/js/Follow.js"
  },
  output: {
    filename: "[name].min.js",
    chunkFilename: chunkFilename,
    path: path.resolve(__dirname, "assets/production/js"),
    publicPath: ASSET_URL
  },
  watch: true,
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: "vue-loader"
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        loader: "babel-loader"
      },
      {
        test: /\.(sa|sc|c)ss$/i,
        use: [
          process.env.NODE_ENV !== "production1"
            ? "vue-style-loader"
            : MiniCssExtractPlugin.loader,
          "css-loader",
          "sass-loader"
        ]
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
  },
  resolve: {
    alias: {
      vue$: "vue/dist/vue.esm.js" // 'vue/dist/vue.common.js' for webpack 1
    }
  }
};
