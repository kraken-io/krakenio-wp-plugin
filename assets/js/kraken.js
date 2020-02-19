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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/css/src/style.scss":
/*!***********************************!*\
  !*** ./assets/css/src/style.scss ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./assets/js/src/index.js":
/*!********************************!*\
  !*** ./assets/js/src/index.js ***!
  \********************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_src_style_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../css/src/style.scss */ "./assets/css/src/style.scss");
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

/***/ })

/******/ });
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2Nzcy9zcmMvc3R5bGUuc2Nzcz84ZDY2Iiwid2VicGFjazovLy8uL2Fzc2V0cy9qcy9zcmMvaW5kZXguanMiXSwibmFtZXMiOlsiJCIsIndpbmRvdyIsImpRdWVyeSIsImRvY3VtZW50Iiwib24iLCJlIiwicHJldmVudERlZmF1bHQiLCIkZWwiLCJuZXh0IiwidG9nZ2xlQ2xhc3MiLCJwYXJlbnQiLCJyZW1vdmVDbGFzcyIsInJlc2V0IiwiY29uZmlybSIsImtyYWtlbl9vcHRpb25zIiwidGV4dHMiLCJyZXNldF9pbWFnZSIsImlkIiwiZGF0YSIsIiRzcGlubmVyIiwiZmluZCIsImFkZENsYXNzIiwiYWpheCIsInR5cGUiLCJ1cmwiLCJhamF4X3VybCIsImFjdGlvbiIsIm5vbmNlIiwic3VjY2VzcyIsInJlc3BvbnNlIiwicGFyZW50cyIsInJlcGxhY2VXaXRoIiwiaHRtbCIsImFsZXJ0IiwiZXJyb3JfcmVzZXQiLCJlcnJvciIsInBhZ2VzIiwicGFnZSIsIm9wdGltaXplZCIsImlkcyIsIm9wdGltaXplSW1hZ2VBamF4Q2FsbGJhY2siLCIkdGFibGUiLCJzaGlmdCIsInVuZGVmaW5lZCIsImdldFVub3B0aW1pemVkSW1hZ2VzUGFnZXMiLCJhcHBlbmQiLCJ0ZXh0IiwicGFnZWQiLCJsZW5ndGgiLCJkcmF3Q2lyY2xlIiwid2lkdGgiLCJjYW52YXMiLCJjb250ZXh0IiwiZ2V0Q29udGV4dCIsInN0YXJ0UG9pbnQiLCJNYXRoIiwiUEkiLCJsaW5lV2lkdGgiLCJwZXJjZW50IiwiY29sb3IiLCJvbmVQZXJjZW50IiwicmFkaXVzIiwiY2VudGVyIiwiZGVlZ3JlIiwic3Ryb2tlU3R5bGUiLCJjbGVhclJlY3QiLCJiZWdpblBhdGgiLCJhcmMiLCJzdHJva2UiXSwibWFwcGluZ3MiOiI7UUFBQTtRQUNBOztRQUVBO1FBQ0E7O1FBRUE7UUFDQTtRQUNBO1FBQ0E7UUFDQTtR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RG5CLFVBQVUsQ0FBRWpELENBQUMsQ0FBRSx5QkFBRixDQUFILEVBQWtDLEdBQWxDLENBQVYsQyIsImZpbGUiOiJqcy9rcmFrZW4uanMiLCJzb3VyY2VzQ29udGVudCI6WyIgXHQvLyBUaGUgbW9kdWxlIGNhY2hlXG4gXHR2YXIgaW5zdGFsbGVkTW9kdWxlcyA9IHt9O1xuXG4gXHQvLyBUaGUgcmVxdWlyZSBmdW5jdGlvblxuIFx0ZnVuY3Rpb24gX193ZWJwYWNrX3JlcXVpcmVfXyhtb2R1bGVJZCkge1xuXG4gXHRcdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuIFx0XHRpZihpbnN0YWxsZWRNb2R1bGVzW21vZHVsZUlkXSkge1xuIFx0XHRcdHJldHVybiBpbnN0YWxsZWRNb2R1bGVzW21vZHVsZUlkXS5leHBvcnRzO1xuIFx0XHR9XG4gXHRcdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG4gXHRcdHZhciBtb2R1bGUgPSBpbnN0YWxsZWRNb2R1bGVzW21vZHVsZUlkXSA9IHtcbiBcdFx0XHRpOiBtb2R1bGVJZCxcbiBcdFx0XHRsOiBmYWxzZSxcbiBcdFx0XHRleHBvcnRzOiB7fVxuIFx0XHR9O1xuXG4gXHRcdC8vIEV4ZWN1dGUgdGhlIG1vZHVsZSBmdW5jdGlvblxuIFx0XHRtb2R1bGVzW21vZHVsZUlkXS5jYWxsKG1vZHVsZS5leHBvcnRzLCBtb2R1bGUsIG1vZHVsZS5leHBvcnRzLCBfX3dlYnBhY2tfcmVxdWlyZV9fKTtcblxuIFx0XHQvLyBGbGFnIHRoZSBtb2R1bGUgYXMgbG9hZGVkXG4gXHRcdG1vZHVsZS5sID0gdHJ1ZTtcblxuIFx0XHQvLyBSZXR1cm4gdGhlIGV4cG9ydHMgb2YgdGhlIG1vZHVsZVxuIFx0XHRyZXR1cm4gbW9kdWxlLmV4cG9ydHM7XG4gXHR9XG5cblxuIFx0Ly8gZXhwb3NlIHRoZSBtb2R1bGVzIG9iamVjdCAoX193ZWJwYWNrX21vZHVsZXNfXylcbiBcdF9fd2VicGFja19yZXF1aXJlX18ubSA9IG1vZHVsZXM7XG5cbiBcdC8vIGV4cG9zZSB0aGUgbW9kdWxlIGNhY2hlXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLmMgPSBpbnN0YWxsZWRNb2R1bGVzO1xuXG4gXHQvLyBkZWZpbmUgZ2V0dGVyIGZ1bmN0aW9uIGZvciBoYXJtb255IGV4cG9ydHNcbiBcdF9fd2VicGFja19yZXF1aXJlX18uZCA9IGZ1bmN0aW9uKGV4cG9ydHMsIG5hbWUsIGdldHRlcikge1xuIFx0XHRpZighX193ZWJwYWNrX3JlcXVpcmVfXy5vKGV4cG9ydHMsIG5hbWUpKSB7XG4gXHRcdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIG5hbWUsIHsgZW51bWVyYWJsZTogdHJ1ZSwgZ2V0OiBnZXR0ZXIgfSk7XG4gXHRcdH1cbiBcdH07XG5cbiBcdC8vIGRlZmluZSBfX2VzTW9kdWxlIG9uIGV4cG9ydHNcbiBcdF9fd2VicGFja19yZXF1aXJlX18uciA9IGZ1bmN0aW9uKGV4cG9ydHMpIHtcbiBcdFx0aWYodHlwZW9mIFN5bWJvbCAhPT0gJ3VuZGVmaW5lZCcgJiYgU3ltYm9sLnRvU3RyaW5nVGFnKSB7XG4gXHRcdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIFN5bWJvbC50b1N0cmluZ1RhZywgeyB2YWx1ZTogJ01vZHVsZScgfSk7XG4gXHRcdH1cbiBcdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsICdfX2VzTW9kdWxlJywgeyB2YWx1ZTogdHJ1ZSB9KTtcbiBcdH07XG5cbiBcdC8vIGNyZWF0ZSBhIGZha2UgbmFtZXNwYWNlIG9iamVjdFxuIFx0Ly8gbW9kZSAmIDE6IHZhbHVlIGlzIGEgbW9kdWxlIGlkLCByZXF1aXJlIGl0XG4gXHQvLyBtb2RlICYgMjogbWVyZ2UgYWxsIHByb3BlcnRpZXMgb2YgdmFsdWUgaW50byB0aGUgbnNcbiBcdC8vIG1vZGUgJiA0OiByZXR1cm4gdmFsdWUgd2hlbiBhbHJlYWR5IG5zIG9iamVjdFxuIFx0Ly8gbW9kZSAmIDh8MTogYmVoYXZlIGxpa2UgcmVxdWlyZVxuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy50ID0gZnVuY3Rpb24odmFsdWUsIG1vZGUpIHtcbiBcdFx0aWYobW9kZSAmIDEpIHZhbHVlID0gX193ZWJwYWNrX3JlcXVpcmVfXyh2YWx1ZSk7XG4gXHRcdGlmKG1vZGUgJiA4KSByZXR1cm4gdmFsdWU7XG4gXHRcdGlmKChtb2RlICYgNCkgJiYgdHlwZW9mIHZhbHVlID09PSAnb2JqZWN0JyAmJiB2YWx1ZSAmJiB2YWx1ZS5fX2VzTW9kdWxlKSByZXR1cm4gdmFsdWU7XG4gXHRcdHZhciBucyA9IE9iamVjdC5jcmVhdGUobnVsbCk7XG4gXHRcdF9fd2VicGFja19yZXF1aXJlX18ucihucyk7XG4gXHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShucywgJ2RlZmF1bHQnLCB7IGVudW1lcmFibGU6IHRydWUsIHZhbHVlOiB2YWx1ZSB9KTtcbiBcdFx0aWYobW9kZSAmIDIgJiYgdHlwZW9mIHZhbHVlICE9ICdzdHJpbmcnKSBmb3IodmFyIGtleSBpbiB2YWx1ZSkgX193ZWJwYWNrX3JlcXVpcmVfXy5kKG5zLCBrZXksIGZ1bmN0aW9uKGtleSkgeyByZXR1cm4gdmFsdWVba2V5XTsgfS5iaW5kKG51bGwsIGtleSkpO1xuIFx0XHRyZXR1cm4gbnM7XG4gXHR9O1xuXG4gXHQvLyBnZXREZWZhdWx0RXhwb3J0IGZ1bmN0aW9uIGZvciBjb21wYXRpYmlsaXR5IHdpdGggbm9uLWhhcm1vbnkgbW9kdWxlc1xuIFx0X193ZWJwYWNrX3JlcXVpcmVfXy5uID0gZnVuY3Rpb24obW9kdWxlKSB7XG4gXHRcdHZhciBnZXR0ZXIgPSBtb2R1bGUgJiYgbW9kdWxlLl9fZXNNb2R1bGUgP1xuIFx0XHRcdGZ1bmN0aW9uIGdldERlZmF1bHQoKSB7IHJldHVybiBtb2R1bGVbJ2RlZmF1bHQnXTsgfSA6XG4gXHRcdFx0ZnVuY3Rpb24gZ2V0TW9kdWxlRXhwb3J0cygpIHsgcmV0dXJuIG1vZHVsZTsgfTtcbiBcdFx0X193ZWJwYWNrX3JlcXVpcmVfXy5kKGdldHRlciwgJ2EnLCBnZXR0ZXIpO1xuIFx0XHRyZXR1cm4gZ2V0dGVyO1xuIFx0fTtcblxuIFx0Ly8gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSBmdW5jdGlvbihvYmplY3QsIHByb3BlcnR5KSB7IHJldHVybiBPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwob2JqZWN0LCBwcm9wZXJ0eSk7IH07XG5cbiBcdC8vIF9fd2VicGFja19wdWJsaWNfcGF0aF9fXG4gXHRfX3dlYnBhY2tfcmVxdWlyZV9fLnAgPSBcIlwiO1xuXG5cbiBcdC8vIExvYWQgZW50cnkgbW9kdWxlIGFuZCByZXR1cm4gZXhwb3J0c1xuIFx0cmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18oX193ZWJwYWNrX3JlcXVpcmVfXy5zID0gXCIuL2Fzc2V0cy9qcy9zcmMvaW5kZXguanNcIik7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW4iLCJpbXBvcnQgJy4uLy4uL2Nzcy9zcmMvc3R5bGUuc2Nzcyc7XG5jb25zdCAkID0gd2luZG93LmpRdWVyeTtcblxuJCggZG9jdW1lbnQgKS5vbiggJ2NsaWNrJywgJy5rcmFrZW4tc3RhdHMtYWN0aW9uLXNob3ctZGV0YWlscycsIGZ1bmN0aW9uKCBlICkge1xuXHRlLnByZXZlbnREZWZhdWx0KCk7XG5cblx0Y29uc3QgJGVsID0gJCggdGhpcyApO1xuXHQkZWwubmV4dCgpLnRvZ2dsZUNsYXNzKCAnaXMtdmlzaWJsZScgKTtcbn0gKTtcblxuJCggZG9jdW1lbnQgKS5vbiggJ2NsaWNrJywgJy5rcmFrZW4tc3RhdHMtYWN0aW9uLXBvcHVwLWNsb3NlJywgZnVuY3Rpb24oIGUgKSB7XG5cdGUucHJldmVudERlZmF1bHQoKTtcblx0JCggdGhpcyApLnBhcmVudCgpLnJlbW92ZUNsYXNzKCAnaXMtdmlzaWJsZScgKTtcbn0gKTtcblxuJCggZG9jdW1lbnQgKS5vbiggJ2NsaWNrJywgJy5rcmFrZW4tc3RhdHMtYWN0aW9uLXJlc2V0LWltYWdlJywgZnVuY3Rpb24oIGUgKSB7XG5cdGUucHJldmVudERlZmF1bHQoKTtcblxuXHRjb25zdCByZXNldCA9IGNvbmZpcm0oIHdpbmRvdy5rcmFrZW5fb3B0aW9ucy50ZXh0cy5yZXNldF9pbWFnZSApO1xuXG5cdGlmICggISByZXNldCApIHtcblx0XHRyZXR1cm47XG5cdH1cblxuXHRjb25zdCAkZWwgPSAkKCB0aGlzICk7XG5cdGNvbnN0IGlkID0gJGVsLmRhdGEoICdpZCcgKTtcblx0Y29uc3QgJHNwaW5uZXIgPSAkZWwuZmluZCggJy5zcGlubmVyJyApO1xuXG5cdCRzcGlubmVyLmFkZENsYXNzKCAnaXMtYWN0aXZlJyApO1xuXG5cdCQuYWpheCgge1xuXHRcdHR5cGU6ICdQT1NUJyxcblx0XHR1cmw6IHdpbmRvdy5rcmFrZW5fb3B0aW9ucy5hamF4X3VybCxcblx0XHRkYXRhOiB7XG5cdFx0XHRhY3Rpb246ICdrcmFrZW5fcmVzZXRfaW1hZ2UnLFxuXHRcdFx0aWQsXG5cdFx0XHRub25jZTogd2luZG93LmtyYWtlbl9vcHRpb25zLm5vbmNlLFxuXHRcdH0sXG5cdFx0c3VjY2VzcyggcmVzcG9uc2UgKSB7XG5cdFx0XHRpZiAoIHJlc3BvbnNlLnN1Y2Nlc3MgKSB7XG5cdFx0XHRcdCRlbC5wYXJlbnRzKCAnLmtyYWtlbi1zdGF0cy1tZWRpYS1jb2x1bW4nICkucmVwbGFjZVdpdGgoIHJlc3BvbnNlLmRhdGEuaHRtbCApO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0YWxlcnQoIHdpbmRvdy5rcmFrZW5fb3B0aW9ucy50ZXh0cy5lcnJvcl9yZXNldCApO1xuXHRcdFx0fVxuXHRcdH0sXG5cdFx0ZXJyb3IoKSB7XG5cdFx0XHRhbGVydCggd2luZG93LmtyYWtlbl9vcHRpb25zLnRleHRzLmVycm9yX3Jlc2V0ICk7XG5cdFx0fSxcblx0fSApO1xufSApO1xuXG4kKCBkb2N1bWVudCApLm9uKCAnY2xpY2snLCAnLmtyYWtlbi1idXR0b24tb3B0aW1pemUtaW1hZ2UnLCBmdW5jdGlvbiggZSApIHtcblx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdGNvbnN0ICRlbCA9ICQoIHRoaXMgKTtcblx0Y29uc3QgJHNwaW5uZXIgPSAkZWwuZmluZCggJy5zcGlubmVyJyApO1xuXHRjb25zdCBpZCA9ICRlbC5kYXRhKCAnaWQnICk7XG5cblx0JHNwaW5uZXIuYWRkQ2xhc3MoICdpcy1hY3RpdmUnICk7XG5cblx0JC5hamF4KCB7XG5cdFx0dHlwZTogJ1BPU1QnLFxuXHRcdHVybDogd2luZG93LmtyYWtlbl9vcHRpb25zLmFqYXhfdXJsLFxuXHRcdGRhdGE6IHtcblx0XHRcdGFjdGlvbjogJ2tyYWtlbl9vcHRpbWl6ZV9pbWFnZScsXG5cdFx0XHRpZCxcblx0XHRcdHR5cGU6ICdzaW5nbGUnLFxuXHRcdFx0bm9uY2U6IHdpbmRvdy5rcmFrZW5fb3B0aW9ucy5ub25jZSxcblx0XHR9LFxuXHRcdHN1Y2Nlc3MoIHJlc3BvbnNlICkge1xuXHRcdFx0aWYgKCByZXNwb25zZS5zdWNjZXNzICkge1xuXHRcdFx0XHQkZWwucGFyZW50cyggJy5rcmFrZW4tc3RhdHMtbWVkaWEtY29sdW1uJyApLnJlcGxhY2VXaXRoKCByZXNwb25zZS5kYXRhLmh0bWwgKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGFsZXJ0KCB3aW5kb3cua3Jha2VuX29wdGlvbnMudGV4dHMuZXJyb3JfcmVzZXQgKTtcblx0XHRcdFx0JHNwaW5uZXIucmVtb3ZlQ2xhc3MoICdpcy1hY3RpdmUnICk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHRlcnJvcigpIHtcblx0XHRcdGFsZXJ0KCB3aW5kb3cua3Jha2VuX29wdGlvbnMudGV4dHMuZXJyb3JfcmVzZXQgKTtcblx0XHRcdCRzcGlubmVyLnJlbW92ZUNsYXNzKCAnaXMtYWN0aXZlJyApO1xuXHRcdH0sXG5cdH0gKTtcbn0gKTtcblxuJCggZG9jdW1lbnQgKS5vbiggJ2NsaWNrJywgJy5rcmFrZW4tYnV0dG9uLWJ1bGstb3B0aW1pemUnLCBmdW5jdGlvbiggZSApIHtcblx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdGNvbnN0ICRlbCA9ICQoIHRoaXMgKTtcblx0Y29uc3QgJHNwaW5uZXIgPSAkZWwuZmluZCggJy5zcGlubmVyJyApO1xuXHRjb25zdCBwYWdlcyA9ICRlbC5kYXRhKCAncGFnZXMnICk7XG5cdGNvbnN0IHBhZ2UgPSAxO1xuXHRjb25zdCBvcHRpbWl6ZWQgPSAwO1xuXHRjb25zdCBpZHMgPSAkZWwuZGF0YSggJ2lkcycgKTtcblxuXHQkZWwucGFyZW50cyggJy5rcmFrZW4tYnVsay1hY3Rpb25zJyApLmFkZENsYXNzKCAnaXMtYWN0aXZlJyApO1xuXHQkc3Bpbm5lci5hZGRDbGFzcyggJ2lzLWFjdGl2ZScgKTtcblxuXHRvcHRpbWl6ZUltYWdlQWpheENhbGxiYWNrKCAkZWwsIGlkcywgb3B0aW1pemVkLCBwYWdlcywgcGFnZSApO1xufSApO1xuXG5mdW5jdGlvbiBvcHRpbWl6ZUltYWdlQWpheENhbGxiYWNrKCAkZWwsIGlkcywgb3B0aW1pemVkLCBwYWdlcywgcGFnZSApIHtcblx0Y29uc3QgJHRhYmxlID0gJGVsLnBhcmVudHMoICcua3Jha2VuLWJ1bGstb3B0aW1pemVyJyApLmZpbmQoICcua3Jha2VuLWJ1bGstdGFibGUgdGJvZHknICk7XG5cdGNvbnN0IGlkID0gaWRzLnNoaWZ0KCk7XG5cdGNvbnN0ICRzcGlubmVyID0gJGVsLmZpbmQoICcuc3Bpbm5lcicgKTtcblxuXHRpZiAoIHVuZGVmaW5lZCA9PT0gaWQgKSB7XG5cdFx0aWYgKCBwYWdlIDwgcGFnZXMgKSB7XG5cdFx0XHRwYWdlID0gcGFnZSArIDE7XG5cdFx0XHRnZXRVbm9wdGltaXplZEltYWdlc1BhZ2VzKCAkZWwsIG9wdGltaXplZCwgcGFnZXMsIHBhZ2UgKTtcblx0XHR9IGVsc2Uge1xuXHRcdFx0JHNwaW5uZXIucmVtb3ZlQ2xhc3MoICdpcy1hY3RpdmUnICk7XG5cdFx0fVxuXG5cdFx0cmV0dXJuIGZhbHNlO1xuXHR9XG5cblx0b3B0aW1pemVkID0gb3B0aW1pemVkICsgMTtcblxuXHQkLmFqYXgoIHtcblx0XHR0eXBlOiAnUE9TVCcsXG5cdFx0dXJsOiB3aW5kb3cua3Jha2VuX29wdGlvbnMuYWpheF91cmwsXG5cdFx0ZGF0YToge1xuXHRcdFx0YWN0aW9uOiAna3Jha2VuX29wdGltaXplX2ltYWdlJyxcblx0XHRcdGlkLFxuXHRcdFx0dHlwZTogJ2J1bGsnLFxuXHRcdFx0bm9uY2U6IHdpbmRvdy5rcmFrZW5fb3B0aW9ucy5ub25jZSxcblx0XHR9LFxuXHRcdHN1Y2Nlc3MoIHJlc3BvbnNlICkge1xuXHRcdFx0aWYgKCByZXNwb25zZS5zdWNjZXNzICkge1xuXHRcdFx0XHQkdGFibGUuYXBwZW5kKCAkKCByZXNwb25zZS5kYXRhLmh0bWwgKSApO1xuXHRcdFx0fVxuXHRcdFx0JCggJy5vcHRpbWl6ZWQnICkudGV4dCggb3B0aW1pemVkICk7XG5cblx0XHRcdG9wdGltaXplSW1hZ2VBamF4Q2FsbGJhY2soICRlbCwgaWRzLCBvcHRpbWl6ZWQsIHBhZ2VzLCBwYWdlICk7XG5cdFx0fSxcblx0XHRlcnJvcigpIHtcblx0XHRcdCQoICcub3B0aW1pemVkJyApLnRleHQoIG9wdGltaXplZCApO1xuXHRcdFx0b3B0aW1pemVJbWFnZUFqYXhDYWxsYmFjayggJGVsLCBpZHMsIG9wdGltaXplZCwgcGFnZXMsIHBhZ2UgKTtcblx0XHR9LFxuXHR9ICk7XG59XG5cbmZ1bmN0aW9uIGdldFVub3B0aW1pemVkSW1hZ2VzUGFnZXMoICRlbCwgb3B0aW1pemVkLCBwYWdlcywgcGFnZSApIHtcblx0JC5hamF4KCB7XG5cdFx0dHlwZTogJ1BPU1QnLFxuXHRcdHVybDogd2luZG93LmtyYWtlbl9vcHRpb25zLmFqYXhfdXJsLFxuXHRcdGRhdGE6IHtcblx0XHRcdGFjdGlvbjogJ2tyYWtlbl9nZXRfYnVsa19wYWdlcycsXG5cdFx0XHRwYWdlZDogcGFnZSxcblx0XHRcdG5vbmNlOiB3aW5kb3cua3Jha2VuX29wdGlvbnMubm9uY2UsXG5cdFx0fSxcblx0XHRzdWNjZXNzKCByZXNwb25zZSApIHtcblx0XHRcdGNvbnN0IGRhdGEgPSByZXNwb25zZS5kYXRhO1xuXHRcdFx0aWYgKCBkYXRhLmlkcy5sZW5ndGggPiAwICkge1xuXHRcdFx0XHRvcHRpbWl6ZUltYWdlQWpheENhbGxiYWNrKCAkZWwsIGRhdGEuaWRzLCBvcHRpbWl6ZWQsIHBhZ2VzLCBwYWdlICk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHRlcnJvcigpIHtcblx0XHR9LFxuXHR9ICk7XG59XG5cbiQoIGRvY3VtZW50ICkub24oICdjbGljaycsICcua3Jha2VuLWJ1bGstY2xvc2UtbW9kYWwnLCBmdW5jdGlvbiggZSApIHtcblx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdCQoIHRoaXMgKS5wYXJlbnRzKCAnLmtyYWtlbi1tb2RhbCcgKS5yZW1vdmVDbGFzcyggJ2lzLWFjdGl2ZScgKTtcbn0gKTtcblxuZnVuY3Rpb24gZHJhd0NpcmNsZSggJGVsLCB3aWR0aCApIHtcblx0aWYgKCAkZWwubGVuZ3RoID09PSAwICkge1xuXHRcdHJldHVybiBmYWxzZTtcblx0fVxuXG5cdGNvbnN0IGNhbnZhcyA9ICRlbC5maW5kKCAnY2FudmFzJyApWyAwIF07XG5cdGNvbnN0IGNvbnRleHQgPSBjYW52YXMuZ2V0Q29udGV4dCggJzJkJyApO1xuXHRjb25zdCBzdGFydFBvaW50ID0gTWF0aC5QSSAvIDE4MDtcblx0Y29uc3QgbGluZVdpZHRoID0gMTA7XG5cdGNvbnN0IHBlcmNlbnQgPSAkZWwuZGF0YSggJ3BlcmNlbnQnICk7XG5cdGNvbnN0IGNvbG9yID0gJGVsLmRhdGEoICdjb2xvcicgKTtcblx0Y29uc3Qgb25lUGVyY2VudCA9IDM2MCAvIDEwMDtcblx0Y29uc3QgcmFkaXVzID0gKCB3aWR0aCAtIGxpbmVXaWR0aCApIC8gMjtcblx0Y29uc3QgY2VudGVyID0gd2lkdGggLyAyO1xuXHRjb25zdCBkZWVncmUgPSBvbmVQZXJjZW50ICogcGVyY2VudDtcblxuXHRjb250ZXh0LnN0cm9rZVN0eWxlID0gY29sb3I7XG5cdGNvbnRleHQubGluZVdpZHRoID0gbGluZVdpZHRoO1xuXHRjb250ZXh0LmNsZWFyUmVjdCggMCwgMCwgd2lkdGgsIHdpZHRoICk7XG5cdGNvbnRleHQuYmVnaW5QYXRoKCk7XG5cdGNvbnRleHQuYXJjKCBjZW50ZXIsIGNlbnRlciwgcmFkaXVzLCBzdGFydFBvaW50ICogMjcwLCBzdGFydFBvaW50ICogKCAyNzAgKyBkZWVncmUgKSApO1xuXHRjb250ZXh0LnN0cm9rZSgpO1xufVxuXG5kcmF3Q2lyY2xlKCAkKCAnLmtyYWtlbi1wcm9ncmVzcy1jaXJjbGUnICksIDEyMCApO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==