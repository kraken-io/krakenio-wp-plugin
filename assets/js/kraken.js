/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_src_style_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
/* harmony import */ var _css_src_style_scss__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_css_src_style_scss__WEBPACK_IMPORTED_MODULE_0__);

var $ = window.jQuery;
$(document).on('click', '.kraken-stats-action-show-details', function (e) {
  e.preventDefault();
  var $el = $(this);
  $el.next().toggleClass('is-visible');
});
$(document).on('click', '.kraken-stats-action-popup-close', function (e) {
  e.preventDefault();
  $(this).parent().removeClass('is-visible');
});
$(document).on('click', '.kraken-stats-action-reset-image', function (e) {
  e.preventDefault();
  var reset = confirm(window.kraken_options.texts.reset_image);

  if (!reset) {
    return;
  }

  var $el = $(this);
  var id = $el.data('id');
  var $spinner = $el.find('.spinner');
  $spinner.addClass('is-active');
  $.ajax({
    type: 'POST',
    url: window.kraken_options.ajax_url,
    data: {
      action: 'kraken_reset_image',
      id: id,
      nonce: window.kraken_options.nonce
    },
    success: function success(response) {
      if (response.success) {
        $el.parents('.kraken-stats-media-column').replaceWith(response.data.html);
      } else {
        alert(window.kraken_options.texts.error_reset);
      }
    },
    error: function error() {
      alert(window.kraken_options.texts.error_reset);
    }
  });
});
$(document).on('click', '.kraken-button-optimize-image', function (e) {
  e.preventDefault();
  var $el = $(this);
  var $spinner = $el.find('.spinner');
  var id = $el.data('id');
  $spinner.addClass('is-active');
  $.ajax({
    type: 'POST',
    url: window.kraken_options.ajax_url,
    data: {
      action: 'kraken_optimize_image',
      id: id,
      type: 'single',
      nonce: window.kraken_options.nonce
    },
    success: function success(response) {
      if (response.success) {
        $el.parents('.kraken-stats-media-column').replaceWith(response.data.html);
      } else {
        alert(window.kraken_options.texts.error_reset);
        $spinner.removeClass('is-active');
      }
    },
    error: function error() {
      alert(window.kraken_options.texts.error_reset);
      $spinner.removeClass('is-active');
    }
  });
});
$(document).on('click', '.kraken-button-bulk-optimize', function (e) {
  e.preventDefault();
  var $el = $(this);
  var $spinner = $el.find('.spinner');
  var pages = $el.data('pages');
  var page = 1;
  var optimized = 0;
  var ids = $el.data('ids');
  $el.parents('.kraken-bulk-actions').addClass('is-active');
  $spinner.addClass('is-active');
  optimizeImageAjaxCallback($el, ids, optimized, pages, page);
});

function optimizeImageAjaxCallback($el, ids, optimized, pages, page) {
  var $table = $el.parents('.kraken-bulk-optimizer').find('.kraken-bulk-table tbody');
  var id = ids.shift();
  var $spinner = $el.find('.spinner');

  if (undefined === id) {
    if (page < pages) {
      page = page + 1;
      getUnoptimizedImagesPages($el, optimized, pages, page);
    } else {
      $spinner.removeClass('is-active');
    }

    return false;
  }

  optimized = optimized + 1;
  $.ajax({
    type: 'POST',
    url: window.kraken_options.ajax_url,
    data: {
      action: 'kraken_optimize_image',
      id: id,
      type: 'bulk',
      nonce: window.kraken_options.nonce
    },
    success: function success(response) {
      if (response.success) {
        $table.append($(response.data.html));
      }

      $('.optimized').text(optimized);
      optimizeImageAjaxCallback($el, ids, optimized, pages, page);
    },
    error: function error() {
      $('.optimized').text(optimized);
      optimizeImageAjaxCallback($el, ids, optimized, pages, page);
    }
  });
}

function getUnoptimizedImagesPages($el, optimized, pages, page) {
  $.ajax({
    type: 'POST',
    url: window.kraken_options.ajax_url,
    data: {
      action: 'kraken_get_bulk_pages',
      paged: page,
      nonce: window.kraken_options.nonce
    },
    success: function success(response) {
      var data = response.data;

      if (data.ids.length > 0) {
        optimizeImageAjaxCallback($el, data.ids, optimized, pages, page);
      }
    },
    error: function error() {}
  });
}

$(document).on('click', '.kraken-bulk-close-modal', function (e) {
  e.preventDefault();
  $(this).parents('.kraken-modal').removeClass('is-active');
});

function drawCircle($el, width) {
  if ($el.length === 0) {
    return false;
  }

  var canvas = $el.find('canvas')[0];
  var context = canvas.getContext('2d');
  var startPoint = Math.PI / 180;
  var lineWidth = 10;
  var percent = $el.data('percent');
  var color = $el.data('color');
  var onePercent = 360 / 100;
  var radius = (width - lineWidth) / 2;
  var center = width / 2;
  var deegre = onePercent * percent;
  context.strokeStyle = color;
  context.lineWidth = lineWidth;
  context.clearRect(0, 0, width, width);
  context.beginPath();
  context.arc(center, center, radius, startPoint * 270, startPoint * (270 + deegre));
  context.stroke();
}

drawCircle($('.kraken-progress-circle'), 120);

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })
/******/ ]);