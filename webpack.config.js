const webpack = require('webpack');
const path = require('path');

const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const TerserJSPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyPlugin = require('copy-webpack-plugin');

let config = {
  entry: {
    style: [
      'bootstrap/dist/css/bootstrap.min.css',
      './templates/assets/css/app.css',
    ],
    main: [
      'jquery', 
      'bootstrap',
      './templates/assets/js/main.js'
    ]
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'public'),
  },
  module: {
    rules: [
      {
        test: /\.css$/i,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              publicPath: '/css/',
            },
          },
          'css-loader',
        ]
      },
      {
        test: /\.(png|jpg|svg|ttf|eot|woff|woff2)$/,
        loader: 'file-loader?name=[name].[ext]'
      },
    ],
  },
  plugins: [
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
    }),
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css',
    }),
    new CopyPlugin({
      patterns: [
        { from: 'templates/assets/index.php', to: 'index.php' },
        { from: 'templates/assets/favicon.ico', to: 'favicon.ico' },
        { from: 'templates/assets/js/datatables-ru.json', to: 'js/' },
        { 
          from: 'templates/assets/images/*', 
          to: 'images',
          flatten: true,
        },
      ],
      options: {
        concurrency: 100,
      },
    }),
    //    new CleanWebpackPlugin(),
  ],
  optimization: {
    minimizer: [
      new TerserJSPlugin({
        test: /\.js$/i,
        exclude: /\/node_modules/,
        parallel: true,
        sourceMap: true
      })
    ]
  },
  stats: true,
}

module.exports = (env, argv) => {
  if (argv.mode === 'production') {
    config.mode = 'production';
    config.optimization.minimize = true;
  } else {
    config.mode = 'development';
    config.optimization.minimize = false;
    config.devtool = 'source-map';
  }
  return config;
};
