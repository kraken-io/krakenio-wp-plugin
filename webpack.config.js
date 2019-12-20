const webpack = require('webpack');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin');

const production = process.env.NODE_ENV === 'production'

const config = {
  entry: {
    "kraken": "./assets/js/src/index.js",
    "kraken.min": "./assets/js/src/index.js",
  },
  output: {
    path: path.resolve( __dirname, 'assets' ),
    filename: 'js/[name].js'
  },
  mode: production ? 'production' : 'development',
  devtool: production ? false : 'inline-source-map',
  watch: !production,
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [ '@wordpress/default' ]
          }
        },
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false,
              import: false,
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              minimize: false,
              plugins: [
                require('autoprefixer')()
              ]
            }
          },
          {
            loader: 'sass-loader'
          }
        ]
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/kraken.css',
      chunkFilename: '[id].css',
    }),
    new MiniCssExtractPlugin({
      filename: 'css/kraken.min.css',
      chunkFilename: '[id].css',
    })
  ],
  performance: {
    hints: false,
  },
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        include: /\.min\.js$/,
      }),
      new OptimizeCSSAssetsPlugin({
        assetNameRegExp: /\.min\.css$/,
        cssProcessorOptions: { discardComments: { removeAll: true } },
        canPrint: true
      }),
    ],
  },
};

module.exports = config;