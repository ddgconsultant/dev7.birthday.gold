/*! © JSOcean DataGrid v2.0.2 */
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
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/immer/dist/immer.esm.js
function n(n){for(var t=arguments.length,r=Array(t>1?t-1:0),e=1;e<t;e++)r[e-1]=arguments[e];if(false){ var i, o; }throw Error("[Immer] minified error nr: "+n+(r.length?" "+r.map((function(n){return"'"+n+"'"})).join(","):"")+". Find the full error at: https://bit.ly/3cXEKWf")}function t(n){return!!n&&!!n[Q]}function r(n){return!!n&&(function(n){if(!n||"object"!=typeof n)return!1;var t=Object.getPrototypeOf(n);if(null===t)return!0;var r=Object.hasOwnProperty.call(t,"constructor")&&t.constructor;return r===Object||"function"==typeof r&&Function.toString.call(r)===Z}(n)||Array.isArray(n)||!!n[L]||!!n.constructor[L]||s(n)||v(n))}function e(r){return t(r)||n(23,r),r[Q].t}function immer_esm_i(n,t,r){void 0===r&&(r=!1),0===o(n)?(r?Object.keys:nn)(n).forEach((function(e){r&&"symbol"==typeof e||t(e,n[e],n)})):n.forEach((function(r,e){return t(e,r,n)}))}function o(n){var t=n[Q];return t?t.i>3?t.i-4:t.i:Array.isArray(n)?1:s(n)?2:v(n)?3:0}function u(n,t){return 2===o(n)?n.has(t):Object.prototype.hasOwnProperty.call(n,t)}function a(n,t){return 2===o(n)?n.get(t):n[t]}function f(n,t,r){var e=o(n);2===e?n.set(t,r):3===e?(n.delete(t),n.add(r)):n[t]=r}function c(n,t){return n===t?0!==n||1/n==1/t:n!=n&&t!=t}function s(n){return X&&n instanceof Map}function v(n){return q&&n instanceof Set}function p(n){return n.o||n.t}function l(n){if(Array.isArray(n))return Array.prototype.slice.call(n);var t=tn(n);delete t[Q];for(var r=nn(t),e=0;e<r.length;e++){var i=r[e],o=t[i];!1===o.writable&&(o.writable=!0,o.configurable=!0),(o.get||o.set)&&(t[i]={configurable:!0,writable:!0,enumerable:o.enumerable,value:n[i]})}return Object.create(Object.getPrototypeOf(n),t)}function d(n,e){return void 0===e&&(e=!1),y(n)||t(n)||!r(n)?n:(o(n)>1&&(n.set=n.add=n.clear=n.delete=h),Object.freeze(n),e&&immer_esm_i(n,(function(n,t){return d(t,!0)}),!0),n)}function h(){n(2)}function y(n){return null==n||"object"!=typeof n||Object.isFrozen(n)}function b(t){var r=rn[t];return r||n(18,t),r}function m(n,t){rn[n]||(rn[n]=t)}function _(){return true||false,U}function immer_esm_j(n,t){t&&(b("Patches"),n.u=[],n.s=[],n.v=t)}function O(n){g(n),n.p.forEach(S),n.p=null}function g(n){n===U&&(U=n.l)}function w(n){return U={p:[],l:U,h:n,m:!0,_:0}}function S(n){var t=n[Q];0===t.i||1===t.i?t.j():t.O=!0}function P(t,e){e._=e.p.length;var i=e.p[0],o=void 0!==t&&t!==i;return e.h.g||b("ES5").S(e,t,o),o?(i[Q].P&&(O(e),n(4)),r(t)&&(t=M(e,t),e.l||x(e,t)),e.u&&b("Patches").M(i[Q],t,e.u,e.s)):t=M(e,i,[]),O(e),e.u&&e.v(e.u,e.s),t!==H?t:void 0}function M(n,t,r){if(y(t))return t;var e=t[Q];if(!e)return immer_esm_i(t,(function(i,o){return A(n,e,t,i,o,r)}),!0),t;if(e.A!==n)return t;if(!e.P)return x(n,e.t,!0),e.t;if(!e.I){e.I=!0,e.A._--;var o=4===e.i||5===e.i?e.o=l(e.k):e.o;immer_esm_i(3===e.i?new Set(o):o,(function(t,i){return A(n,e,o,t,i,r)})),x(n,o,!1),r&&n.u&&b("Patches").R(e,r,n.u,n.s)}return e.o}function A(e,i,o,a,c,s){if( false&&false,t(c)){var v=M(e,c,s&&i&&3!==i.i&&!u(i.D,a)?s.concat(a):void 0);if(f(o,a,v),!t(v))return;e.m=!1}if(r(c)&&!y(c)){if(!e.h.F&&e._<1)return;M(e,c),i&&i.A.l||x(e,c)}}function x(n,t,r){void 0===r&&(r=!1),n.h.F&&n.m&&d(t,r)}function z(n,t){var r=n[Q];return(r?p(r):n)[t]}function I(n,t){if(t in n)for(var r=Object.getPrototypeOf(n);r;){var e=Object.getOwnPropertyDescriptor(r,t);if(e)return e;r=Object.getPrototypeOf(r)}}function k(n){n.P||(n.P=!0,n.l&&k(n.l))}function E(n){n.o||(n.o=l(n.t))}function R(n,t,r){var e=s(t)?b("MapSet").N(t,r):v(t)?b("MapSet").T(t,r):n.g?function(n,t){var r=Array.isArray(n),e={i:r?1:0,A:t?t.A:_(),P:!1,I:!1,D:{},l:t,t:n,k:null,o:null,j:null,C:!1},i=e,o=en;r&&(i=[e],o=on);var u=Proxy.revocable(i,o),a=u.revoke,f=u.proxy;return e.k=f,e.j=a,f}(t,r):b("ES5").J(t,r);return(r?r.A:_()).p.push(e),e}function D(e){return t(e)||n(22,e),function n(t){if(!r(t))return t;var e,u=t[Q],c=o(t);if(u){if(!u.P&&(u.i<4||!b("ES5").K(u)))return u.t;u.I=!0,e=F(t,c),u.I=!1}else e=F(t,c);return immer_esm_i(e,(function(t,r){u&&a(u.t,t)===r||f(e,t,n(r))})),3===c?new Set(e):e}(e)}function F(n,t){switch(t){case 2:return new Map(n);case 3:return Array.from(n)}return l(n)}function N(){function r(n,t){var r=s[n];return r?r.enumerable=t:s[n]=r={configurable:!0,enumerable:t,get:function(){var t=this[Q];return false&&false,en.get(t,n)},set:function(t){var r=this[Q]; false&&false,en.set(r,n,t)}},r}function e(n){for(var t=n.length-1;t>=0;t--){var r=n[t][Q];if(!r.P)switch(r.i){case 5:a(r)&&k(r);break;case 4:o(r)&&k(r)}}}function o(n){for(var t=n.t,r=n.k,e=nn(r),i=e.length-1;i>=0;i--){var o=e[i];if(o!==Q){var a=t[o];if(void 0===a&&!u(t,o))return!0;var f=r[o],s=f&&f[Q];if(s?s.t!==a:!c(f,a))return!0}}var v=!!t[Q];return e.length!==nn(t).length+(v?0:1)}function a(n){var t=n.k;if(t.length!==n.t.length)return!0;var r=Object.getOwnPropertyDescriptor(t,t.length-1);return!(!r||r.get)}function f(t){t.O&&n(3,JSON.stringify(p(t)))}var s={};m("ES5",{J:function(n,t){var e=Array.isArray(n),i=function(n,t){if(n){for(var e=Array(t.length),i=0;i<t.length;i++)Object.defineProperty(e,""+i,r(i,!0));return e}var o=tn(t);delete o[Q];for(var u=nn(o),a=0;a<u.length;a++){var f=u[a];o[f]=r(f,n||!!o[f].enumerable)}return Object.create(Object.getPrototypeOf(t),o)}(e,n),o={i:e?5:4,A:t?t.A:_(),P:!1,I:!1,D:{},l:t,t:n,k:i,o:null,O:!1,C:!1};return Object.defineProperty(i,Q,{value:o,writable:!0}),i},S:function(n,r,o){o?t(r)&&r[Q].A===n&&e(n.p):(n.u&&function n(t){if(t&&"object"==typeof t){var r=t[Q];if(r){var e=r.t,o=r.k,f=r.D,c=r.i;if(4===c)immer_esm_i(o,(function(t){t!==Q&&(void 0!==e[t]||u(e,t)?f[t]||n(o[t]):(f[t]=!0,k(r)))})),immer_esm_i(e,(function(n){void 0!==o[n]||u(o,n)||(f[n]=!1,k(r))}));else if(5===c){if(a(r)&&(k(r),f.length=!0),o.length<e.length)for(var s=o.length;s<e.length;s++)f[s]=!1;else for(var v=e.length;v<o.length;v++)f[v]=!0;for(var p=Math.min(o.length,e.length),l=0;l<p;l++)void 0===f[l]&&n(o[l])}}}}(n.p[0]),e(n.p))},K:function(n){return 4===n.i?o(n):a(n)}})}function T(){function e(n){if(!r(n))return n;if(Array.isArray(n))return n.map(e);if(s(n))return new Map(Array.from(n.entries()).map((function(n){return[n[0],e(n[1])]})));if(v(n))return new Set(Array.from(n).map(e));var t=Object.create(Object.getPrototypeOf(n));for(var i in n)t[i]=e(n[i]);return u(n,L)&&(t[L]=n[L]),t}function f(n){return t(n)?e(n):n}var c="add";m("Patches",{$:function(t,r){return r.forEach((function(r){for(var i=r.path,u=r.op,f=t,s=0;s<i.length-1;s++){var v=o(f),p=""+i[s];0!==v&&1!==v||"__proto__"!==p&&"constructor"!==p||n(24),"function"==typeof f&&"prototype"===p&&n(24),"object"!=typeof(f=a(f,p))&&n(15,i.join("/"))}var l=o(f),d=e(r.value),h=i[i.length-1];switch(u){case"replace":switch(l){case 2:return f.set(h,d);case 3:n(16);default:return f[h]=d}case c:switch(l){case 1:return f.splice(h,0,d);case 2:return f.set(h,d);case 3:return f.add(d);default:return f[h]=d}case"remove":switch(l){case 1:return f.splice(h,1);case 2:return f.delete(h);case 3:return f.delete(r.value);default:return delete f[h]}default:n(17,u)}})),t},R:function(n,t,r,e){switch(n.i){case 0:case 4:case 2:return function(n,t,r,e){var o=n.t,s=n.o;immer_esm_i(n.D,(function(n,i){var v=a(o,n),p=a(s,n),l=i?u(o,n)?"replace":c:"remove";if(v!==p||"replace"!==l){var d=t.concat(n);r.push("remove"===l?{op:l,path:d}:{op:l,path:d,value:p}),e.push(l===c?{op:"remove",path:d}:"remove"===l?{op:c,path:d,value:f(v)}:{op:"replace",path:d,value:f(v)})}}))}(n,t,r,e);case 5:case 1:return function(n,t,r,e){var i=n.t,o=n.D,u=n.o;if(u.length<i.length){var a=[u,i];i=a[0],u=a[1];var s=[e,r];r=s[0],e=s[1]}for(var v=0;v<i.length;v++)if(o[v]&&u[v]!==i[v]){var p=t.concat([v]);r.push({op:"replace",path:p,value:f(u[v])}),e.push({op:"replace",path:p,value:f(i[v])})}for(var l=i.length;l<u.length;l++){var d=t.concat([l]);r.push({op:c,path:d,value:f(u[l])})}i.length<u.length&&e.push({op:"replace",path:t.concat(["length"]),value:i.length})}(n,t,r,e);case 3:return function(n,t,r,e){var i=n.t,o=n.o,u=0;i.forEach((function(n){if(!o.has(n)){var i=t.concat([u]);r.push({op:"remove",path:i,value:n}),e.unshift({op:c,path:i,value:n})}u++})),u=0,o.forEach((function(n){if(!i.has(n)){var o=t.concat([u]);r.push({op:c,path:o,value:n}),e.unshift({op:"remove",path:o,value:n})}u++}))}(n,t,r,e)}},M:function(n,t,r,e){r.push({op:"replace",path:[],value:t===H?void 0:t}),e.push({op:"replace",path:[],value:n.t})}})}function C(){function t(n,t){function r(){this.constructor=n}a(n,t),n.prototype=(r.prototype=t.prototype,new r)}function e(n){n.o||(n.D=new Map,n.o=new Map(n.t))}function o(n){n.o||(n.o=new Set,n.t.forEach((function(t){if(r(t)){var e=R(n.A.h,t,n);n.p.set(t,e),n.o.add(e)}else n.o.add(t)})))}function u(t){t.O&&n(3,JSON.stringify(p(t)))}var a=function(n,t){return(a=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(n,t){n.__proto__=t}||function(n,t){for(var r in t)t.hasOwnProperty(r)&&(n[r]=t[r])})(n,t)},f=function(){function n(n,t){return this[Q]={i:2,l:t,A:t?t.A:_(),P:!1,I:!1,o:void 0,D:void 0,t:n,k:this,C:!1,O:!1},this}t(n,Map);var o=n.prototype;return Object.defineProperty(o,"size",{get:function(){return p(this[Q]).size}}),o.has=function(n){return p(this[Q]).has(n)},o.set=function(n,t){var r=this[Q];return u(r),p(r).has(n)&&p(r).get(n)===t||(e(r),k(r),r.D.set(n,!0),r.o.set(n,t),r.D.set(n,!0)),this},o.delete=function(n){if(!this.has(n))return!1;var t=this[Q];return u(t),e(t),k(t),t.D.set(n,!1),t.o.delete(n),!0},o.clear=function(){var n=this[Q];u(n),p(n).size&&(e(n),k(n),n.D=new Map,immer_esm_i(n.t,(function(t){n.D.set(t,!1)})),n.o.clear())},o.forEach=function(n,t){var r=this;p(this[Q]).forEach((function(e,i){n.call(t,r.get(i),i,r)}))},o.get=function(n){var t=this[Q];u(t);var i=p(t).get(n);if(t.I||!r(i))return i;if(i!==t.t.get(n))return i;var o=R(t.A.h,i,t);return e(t),t.o.set(n,o),o},o.keys=function(){return p(this[Q]).keys()},o.values=function(){var n,t=this,r=this.keys();return(n={})[V]=function(){return t.values()},n.next=function(){var n=r.next();return n.done?n:{done:!1,value:t.get(n.value)}},n},o.entries=function(){var n,t=this,r=this.keys();return(n={})[V]=function(){return t.entries()},n.next=function(){var n=r.next();if(n.done)return n;var e=t.get(n.value);return{done:!1,value:[n.value,e]}},n},o[V]=function(){return this.entries()},n}(),c=function(){function n(n,t){return this[Q]={i:3,l:t,A:t?t.A:_(),P:!1,I:!1,o:void 0,t:n,k:this,p:new Map,O:!1,C:!1},this}t(n,Set);var r=n.prototype;return Object.defineProperty(r,"size",{get:function(){return p(this[Q]).size}}),r.has=function(n){var t=this[Q];return u(t),t.o?!!t.o.has(n)||!(!t.p.has(n)||!t.o.has(t.p.get(n))):t.t.has(n)},r.add=function(n){var t=this[Q];return u(t),this.has(n)||(o(t),k(t),t.o.add(n)),this},r.delete=function(n){if(!this.has(n))return!1;var t=this[Q];return u(t),o(t),k(t),t.o.delete(n)||!!t.p.has(n)&&t.o.delete(t.p.get(n))},r.clear=function(){var n=this[Q];u(n),p(n).size&&(o(n),k(n),n.o.clear())},r.values=function(){var n=this[Q];return u(n),o(n),n.o.values()},r.entries=function(){var n=this[Q];return u(n),o(n),n.o.entries()},r.keys=function(){return this.values()},r[V]=function(){return this.values()},r.forEach=function(n,t){for(var r=this.values(),e=r.next();!e.done;)n.call(t,e.value,e.value,this),e=r.next()},n}();m("MapSet",{N:function(n,t){return new f(n,t)},T:function(n,t){return new c(n,t)}})}function J(){N(),C(),T()}function K(n){return n}function $(n){return n}var G,U,W="undefined"!=typeof Symbol&&"symbol"==typeof Symbol("x"),X="undefined"!=typeof Map,q="undefined"!=typeof Set,B="undefined"!=typeof Proxy&&void 0!==Proxy.revocable&&"undefined"!=typeof Reflect,H=W?Symbol.for("immer-nothing"):((G={})["immer-nothing"]=!0,G),L=W?Symbol.for("immer-draftable"):"__$immer_draftable",Q=W?Symbol.for("immer-state"):"__$immer_state",V="undefined"!=typeof Symbol&&Symbol.iterator||"@@iterator",Y={0:"Illegal state",1:"Immer drafts cannot have computed properties",2:"This object has been frozen and should not be mutated",3:function(n){return"Cannot use a proxy that has been revoked. Did you pass an object from inside an immer function to an async process? "+n},4:"An immer producer returned a new value *and* modified its draft. Either return a new value *or* modify the draft.",5:"Immer forbids circular references",6:"The first or second argument to `produce` must be a function",7:"The third argument to `produce` must be a function or undefined",8:"First argument to `createDraft` must be a plain object, an array, or an immerable object",9:"First argument to `finishDraft` must be a draft returned by `createDraft`",10:"The given draft is already finalized",11:"Object.defineProperty() cannot be used on an Immer draft",12:"Object.setPrototypeOf() cannot be used on an Immer draft",13:"Immer only supports deleting array indices",14:"Immer only supports setting array indices and the 'length' property",15:function(n){return"Cannot apply patch, path doesn't resolve: "+n},16:'Sets cannot have "replace" patches.',17:function(n){return"Unsupported patch operation: "+n},18:function(n){return"The plugin for '"+n+"' has not been loaded into Immer. To enable the plugin, import and call `enable"+n+"()` when initializing your application."},20:"Cannot use proxies if Proxy, Proxy.revocable or Reflect are not available",21:function(n){return"produce can only be called on things that are draftable: plain objects, arrays, Map, Set or classes that are marked with '[immerable]: true'. Got '"+n+"'"},22:function(n){return"'current' expects a draft, got: "+n},23:function(n){return"'original' expects a draft, got: "+n},24:"Patching reserved attributes like __proto__, prototype and constructor is not allowed"},Z=""+Object.prototype.constructor,nn="undefined"!=typeof Reflect&&Reflect.ownKeys?Reflect.ownKeys:void 0!==Object.getOwnPropertySymbols?function(n){return Object.getOwnPropertyNames(n).concat(Object.getOwnPropertySymbols(n))}:Object.getOwnPropertyNames,tn=Object.getOwnPropertyDescriptors||function(n){var t={};return nn(n).forEach((function(r){t[r]=Object.getOwnPropertyDescriptor(n,r)})),t},rn={},en={get:function(n,t){if(t===Q)return n;var e=p(n);if(!u(e,t))return function(n,t,r){var e,i=I(t,r);return i?"value"in i?i.value:null===(e=i.get)||void 0===e?void 0:e.call(n.k):void 0}(n,e,t);var i=e[t];return n.I||!r(i)?i:i===z(n.t,t)?(E(n),n.o[t]=R(n.A.h,i,n)):i},has:function(n,t){return t in p(n)},ownKeys:function(n){return Reflect.ownKeys(p(n))},set:function(n,t,r){var e=I(p(n),t);if(null==e?void 0:e.set)return e.set.call(n.k,r),!0;if(!n.P){var i=z(p(n),t),o=null==i?void 0:i[Q];if(o&&o.t===r)return n.o[t]=r,n.D[t]=!1,!0;if(c(r,i)&&(void 0!==r||u(n.t,t)))return!0;E(n),k(n)}return n.o[t]===r&&"number"!=typeof r&&(void 0!==r||t in n.o)||(n.o[t]=r,n.D[t]=!0,!0)},deleteProperty:function(n,t){return void 0!==z(n.t,t)||t in n.t?(n.D[t]=!1,E(n),k(n)):delete n.D[t],n.o&&delete n.o[t],!0},getOwnPropertyDescriptor:function(n,t){var r=p(n),e=Reflect.getOwnPropertyDescriptor(r,t);return e?{writable:!0,configurable:1!==n.i||"length"!==t,enumerable:e.enumerable,value:r[t]}:e},defineProperty:function(){n(11)},getPrototypeOf:function(n){return Object.getPrototypeOf(n.t)},setPrototypeOf:function(){n(12)}},on={};immer_esm_i(en,(function(n,t){on[n]=function(){return arguments[0]=arguments[0][0],t.apply(this,arguments)}})),on.deleteProperty=function(t,r){return false&&false,en.deleteProperty.call(this,t[0],r)},on.set=function(t,r,e){return false&&false,en.set.call(this,t[0],r,e,t[0])};var un=function(){function e(t){var e=this;this.g=B,this.F=!0,this.produce=function(t,i,o){if("function"==typeof t&&"function"!=typeof i){var u=i;i=t;var a=e;return function(n){var t=this;void 0===n&&(n=u);for(var r=arguments.length,e=Array(r>1?r-1:0),o=1;o<r;o++)e[o-1]=arguments[o];return a.produce(n,(function(n){var r;return(r=i).call.apply(r,[t,n].concat(e))}))}}var f;if("function"!=typeof i&&n(6),void 0!==o&&"function"!=typeof o&&n(7),r(t)){var c=w(e),s=R(e,t,void 0),v=!0;try{f=i(s),v=!1}finally{v?O(c):g(c)}return"undefined"!=typeof Promise&&f instanceof Promise?f.then((function(n){return immer_esm_j(c,o),P(n,c)}),(function(n){throw O(c),n})):(immer_esm_j(c,o),P(f,c))}if(!t||"object"!=typeof t){if((f=i(t))===H)return;return void 0===f&&(f=t),e.F&&d(f,!0),f}n(21,t)},this.produceWithPatches=function(n,t){return"function"==typeof n?function(t){for(var r=arguments.length,i=Array(r>1?r-1:0),o=1;o<r;o++)i[o-1]=arguments[o];return e.produceWithPatches(t,(function(t){return n.apply(void 0,[t].concat(i))}))}:[e.produce(n,t,(function(n,t){r=n,i=t})),r,i];var r,i},"boolean"==typeof(null==t?void 0:t.useProxies)&&this.setUseProxies(t.useProxies),"boolean"==typeof(null==t?void 0:t.autoFreeze)&&this.setAutoFreeze(t.autoFreeze)}var i=e.prototype;return i.createDraft=function(e){r(e)||n(8),t(e)&&(e=D(e));var i=w(this),o=R(this,e,void 0);return o[Q].C=!0,g(i),o},i.finishDraft=function(t,r){var e=t&&t[Q]; false&&(false);var i=e.A;return immer_esm_j(i,r),P(void 0,i)},i.setAutoFreeze=function(n){this.F=n},i.setUseProxies=function(t){t&&!B&&n(20),this.g=t},i.applyPatches=function(n,r){var e;for(e=r.length-1;e>=0;e--){var i=r[e];if(0===i.path.length&&"replace"===i.op){n=i.value;break}}var o=b("Patches").$;return t(n)?o(n,r):this.produce(n,(function(n){return o(n,r.slice(e+1))}))},e}(),an=new un,fn=an.produce,cn=an.produceWithPatches.bind(an),sn=an.setAutoFreeze.bind(an),vn=an.setUseProxies.bind(an),pn=an.applyPatches.bind(an),ln=an.createDraft.bind(an),dn=an.finishDraft.bind(an);/* harmony default export */ var immer_esm = (fn);
//# sourceMappingURL=immer.esm.js.map

// CONCATENATED MODULE: ./node_modules/reselect/es/defaultMemoize.js
// Cache implementation based on Erik Rasmussen's `lru-memoize`:
// https://github.com/erikras/lru-memoize
var NOT_FOUND = 'NOT_FOUND';

function createSingletonCache(equals) {
  var entry;
  return {
    get: function get(key) {
      if (entry && equals(entry.key, key)) {
        return entry.value;
      }

      return NOT_FOUND;
    },
    put: function put(key, value) {
      entry = {
        key: key,
        value: value
      };
    },
    getEntries: function getEntries() {
      return entry ? [entry] : [];
    },
    clear: function clear() {
      entry = undefined;
    }
  };
}

function createLruCache(maxSize, equals) {
  var entries = [];

  function get(key) {
    var cacheIndex = entries.findIndex(function (entry) {
      return equals(key, entry.key);
    }); // We found a cached entry

    if (cacheIndex > -1) {
      var entry = entries[cacheIndex]; // Cached entry not at top of cache, move it to the top

      if (cacheIndex > 0) {
        entries.splice(cacheIndex, 1);
        entries.unshift(entry);
      }

      return entry.value;
    } // No entry found in cache, return sentinel


    return NOT_FOUND;
  }

  function put(key, value) {
    if (get(key) === NOT_FOUND) {
      // TODO Is unshift slow?
      entries.unshift({
        key: key,
        value: value
      });

      if (entries.length > maxSize) {
        entries.pop();
      }
    }
  }

  function getEntries() {
    return entries;
  }

  function clear() {
    entries = [];
  }

  return {
    get: get,
    put: put,
    getEntries: getEntries,
    clear: clear
  };
}

var defaultEqualityCheck = function defaultEqualityCheck(a, b) {
  return a === b;
};
function createCacheKeyComparator(equalityCheck) {
  return function areArgumentsShallowlyEqual(prev, next) {
    if (prev === null || next === null || prev.length !== next.length) {
      return false;
    } // Do this in a for loop (and not a `forEach` or an `every`) so we can determine equality as fast as possible.


    var length = prev.length;

    for (var i = 0; i < length; i++) {
      if (!equalityCheck(prev[i], next[i])) {
        return false;
      }
    }

    return true;
  };
}
// defaultMemoize now supports a configurable cache size with LRU behavior,
// and optional comparison of the result value with existing values
function defaultMemoize(func, equalityCheckOrOptions) {
  var providedOptions = typeof equalityCheckOrOptions === 'object' ? equalityCheckOrOptions : {
    equalityCheck: equalityCheckOrOptions
  };
  var _providedOptions$equa = providedOptions.equalityCheck,
      equalityCheck = _providedOptions$equa === void 0 ? defaultEqualityCheck : _providedOptions$equa,
      _providedOptions$maxS = providedOptions.maxSize,
      maxSize = _providedOptions$maxS === void 0 ? 1 : _providedOptions$maxS,
      resultEqualityCheck = providedOptions.resultEqualityCheck;
  var comparator = createCacheKeyComparator(equalityCheck);
  var cache = maxSize === 1 ? createSingletonCache(comparator) : createLruCache(maxSize, comparator); // we reference arguments instead of spreading them for performance reasons

  function memoized() {
    var value = cache.get(arguments);

    if (value === NOT_FOUND) {
      // @ts-ignore
      value = func.apply(null, arguments);

      if (resultEqualityCheck) {
        var entries = cache.getEntries();
        var matchingEntry = entries.find(function (entry) {
          return resultEqualityCheck(entry.value, value);
        });

        if (matchingEntry) {
          return matchingEntry.value;
        }
      }

      cache.put(arguments, value);
    }

    return value;
  }

  memoized.clearCache = function () {
    return cache.clear();
  };

  return memoized;
}
// CONCATENATED MODULE: ./node_modules/reselect/es/index.js



function getDependencies(funcs) {
  var dependencies = Array.isArray(funcs[0]) ? funcs[0] : funcs;

  if (!dependencies.every(function (dep) {
    return typeof dep === 'function';
  })) {
    var dependencyTypes = dependencies.map(function (dep) {
      return typeof dep === 'function' ? "function " + (dep.name || 'unnamed') + "()" : typeof dep;
    }).join(', ');
    throw new Error("createSelector expects all input-selectors to be functions, but received the following types: [" + dependencyTypes + "]");
  }

  return dependencies;
}

function createSelectorCreator(memoize) {
  for (var _len = arguments.length, memoizeOptionsFromArgs = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    memoizeOptionsFromArgs[_key - 1] = arguments[_key];
  }

  // (memoize: MemoizeFunction, ...memoizeOptions: MemoizerOptions) {
  var createSelector = function createSelector() {
    for (var _len2 = arguments.length, funcs = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      funcs[_key2] = arguments[_key2];
    }

    var _recomputations = 0;

    var _lastResult; // Due to the intricacies of rest params, we can't do an optional arg after `...funcs`.
    // So, start by declaring the default value here.
    // (And yes, the words 'memoize' and 'options' appear too many times in this next sequence.)


    var directlyPassedOptions = {
      memoizeOptions: undefined
    }; // Normally, the result func or "output selector" is the last arg

    var resultFunc = funcs.pop(); // If the result func is actually an _object_, assume it's our options object

    if (typeof resultFunc === 'object') {
      directlyPassedOptions = resultFunc; // and pop the real result func off

      resultFunc = funcs.pop();
    }

    if (typeof resultFunc !== 'function') {
      throw new Error("createSelector expects an output function after the inputs, but received: [" + typeof resultFunc + "]");
    } // Determine which set of options we're using. Prefer options passed directly,
    // but fall back to options given to createSelectorCreator.


    var _directlyPassedOption = directlyPassedOptions,
        _directlyPassedOption2 = _directlyPassedOption.memoizeOptions,
        memoizeOptions = _directlyPassedOption2 === void 0 ? memoizeOptionsFromArgs : _directlyPassedOption2; // Simplifying assumption: it's unlikely that the first options arg of the provided memoizer
    // is an array. In most libs I've looked at, it's an equality function or options object.
    // Based on that, if `memoizeOptions` _is_ an array, we assume it's a full
    // user-provided array of options. Otherwise, it must be just the _first_ arg, and so
    // we wrap it in an array so we can apply it.

    var finalMemoizeOptions = Array.isArray(memoizeOptions) ? memoizeOptions : [memoizeOptions];
    var dependencies = getDependencies(funcs);
    var memoizedResultFunc = memoize.apply(void 0, [function () {
      _recomputations++; // apply arguments instead of spreading for performance.

      return resultFunc.apply(null, arguments);
    }].concat(finalMemoizeOptions)); // If a selector is called with the exact same arguments we don't need to traverse our dependencies again.

    var selector = memoize(function () {
      var params = [];
      var length = dependencies.length;

      for (var i = 0; i < length; i++) {
        // apply arguments instead of spreading and mutate a local list of params for performance.
        // @ts-ignore
        params.push(dependencies[i].apply(null, arguments));
      } // apply arguments instead of spreading for performance.


      _lastResult = memoizedResultFunc.apply(null, params);
      return _lastResult;
    });
    Object.assign(selector, {
      resultFunc: resultFunc,
      memoizedResultFunc: memoizedResultFunc,
      dependencies: dependencies,
      lastResult: function lastResult() {
        return _lastResult;
      },
      recomputations: function recomputations() {
        return _recomputations;
      },
      resetRecomputations: function resetRecomputations() {
        return _recomputations = 0;
      }
    });
    return selector;
  }; // @ts-ignore


  return createSelector;
}
var createSelector = /* #__PURE__ */createSelectorCreator(defaultMemoize);
// Manual definition of state and output arguments
var createStructuredSelector = function createStructuredSelector(selectors, selectorCreator) {
  if (selectorCreator === void 0) {
    selectorCreator = createSelector;
  }

  if (typeof selectors !== 'object') {
    throw new Error('createStructuredSelector expects first argument to be an object ' + ("where each property is a selector, instead received a " + typeof selectors));
  }

  var objectKeys = Object.keys(selectors);
  return selectorCreator( // @ts-ignore
  objectKeys.map(function (key) {
    return selectors[key];
  }), function () {
    for (var _len3 = arguments.length, values = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
      values[_key3] = arguments[_key3];
    }

    return values.reduce(function (composition, value, index) {
      composition[objectKeys[index]] = value;
      return composition;
    }, {});
  });
};
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/objectSpread2.js


function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);

    if (enumerableOnly) {
      symbols = symbols.filter(function (sym) {
        return Object.getOwnPropertyDescriptor(object, sym).enumerable;
      });
    }

    keys.push.apply(keys, symbols);
  }

  return keys;
}

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        _defineProperty(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}
// CONCATENATED MODULE: ./node_modules/redux/es/redux.js


/**
 * Adapted from React: https://github.com/facebook/react/blob/master/packages/shared/formatProdErrorMessage.js
 *
 * Do not require this module directly! Use normal throw error calls. These messages will be replaced with error codes
 * during build.
 * @param {number} code
 */
function formatProdErrorMessage(code) {
  return "Minified Redux error #" + code + "; visit https://redux.js.org/Errors?code=" + code + " for the full message or " + 'use the non-minified dev environment for full errors. ';
}

// Inlined version of the `symbol-observable` polyfill
var $$observable = (function () {
  return typeof Symbol === 'function' && Symbol.observable || '@@observable';
})();

/**
 * These are private action types reserved by Redux.
 * For any unknown actions, you must return the current state.
 * If the current state is undefined, you must return the initial state.
 * Do not reference these action types directly in your code.
 */
var randomString = function randomString() {
  return Math.random().toString(36).substring(7).split('').join('.');
};

var ActionTypes = {
  INIT: "@@redux/INIT" + randomString(),
  REPLACE: "@@redux/REPLACE" + randomString(),
  PROBE_UNKNOWN_ACTION: function PROBE_UNKNOWN_ACTION() {
    return "@@redux/PROBE_UNKNOWN_ACTION" + randomString();
  }
};

/**
 * @param {any} obj The object to inspect.
 * @returns {boolean} True if the argument appears to be a plain object.
 */
function isPlainObject(obj) {
  if (typeof obj !== 'object' || obj === null) return false;
  var proto = obj;

  while (Object.getPrototypeOf(proto) !== null) {
    proto = Object.getPrototypeOf(proto);
  }

  return Object.getPrototypeOf(obj) === proto;
}

// Inlined / shortened version of `kindOf` from https://github.com/jonschlinkert/kind-of
function miniKindOf(val) {
  if (val === void 0) return 'undefined';
  if (val === null) return 'null';
  var type = typeof val;

  switch (type) {
    case 'boolean':
    case 'string':
    case 'number':
    case 'symbol':
    case 'function':
      {
        return type;
      }
  }

  if (Array.isArray(val)) return 'array';
  if (isDate(val)) return 'date';
  if (isError(val)) return 'error';
  var constructorName = ctorName(val);

  switch (constructorName) {
    case 'Symbol':
    case 'Promise':
    case 'WeakMap':
    case 'WeakSet':
    case 'Map':
    case 'Set':
      return constructorName;
  } // other


  return type.slice(8, -1).toLowerCase().replace(/\s/g, '');
}

function ctorName(val) {
  return typeof val.constructor === 'function' ? val.constructor.name : null;
}

function isError(val) {
  return val instanceof Error || typeof val.message === 'string' && val.constructor && typeof val.constructor.stackTraceLimit === 'number';
}

function isDate(val) {
  if (val instanceof Date) return true;
  return typeof val.toDateString === 'function' && typeof val.getDate === 'function' && typeof val.setDate === 'function';
}

function kindOf(val) {
  var typeOfVal = typeof val;

  if (false) {}

  return typeOfVal;
}

/**
 * Creates a Redux store that holds the state tree.
 * The only way to change the data in the store is to call `dispatch()` on it.
 *
 * There should only be a single store in your app. To specify how different
 * parts of the state tree respond to actions, you may combine several reducers
 * into a single reducer function by using `combineReducers`.
 *
 * @param {Function} reducer A function that returns the next state tree, given
 * the current state tree and the action to handle.
 *
 * @param {any} [preloadedState] The initial state. You may optionally specify it
 * to hydrate the state from the server in universal apps, or to restore a
 * previously serialized user session.
 * If you use `combineReducers` to produce the root reducer function, this must be
 * an object with the same shape as `combineReducers` keys.
 *
 * @param {Function} [enhancer] The store enhancer. You may optionally specify it
 * to enhance the store with third-party capabilities such as middleware,
 * time travel, persistence, etc. The only store enhancer that ships with Redux
 * is `applyMiddleware()`.
 *
 * @returns {Store} A Redux store that lets you read the state, dispatch actions
 * and subscribe to changes.
 */

function redux_createStore(reducer, preloadedState, enhancer) {
  var _ref2;

  if (typeof preloadedState === 'function' && typeof enhancer === 'function' || typeof enhancer === 'function' && typeof arguments[3] === 'function') {
    throw new Error( true ? formatProdErrorMessage(0) : undefined);
  }

  if (typeof preloadedState === 'function' && typeof enhancer === 'undefined') {
    enhancer = preloadedState;
    preloadedState = undefined;
  }

  if (typeof enhancer !== 'undefined') {
    if (typeof enhancer !== 'function') {
      throw new Error( true ? formatProdErrorMessage(1) : undefined);
    }

    return enhancer(redux_createStore)(reducer, preloadedState);
  }

  if (typeof reducer !== 'function') {
    throw new Error( true ? formatProdErrorMessage(2) : undefined);
  }

  var currentReducer = reducer;
  var currentState = preloadedState;
  var currentListeners = [];
  var nextListeners = currentListeners;
  var isDispatching = false;
  /**
   * This makes a shallow copy of currentListeners so we can use
   * nextListeners as a temporary list while dispatching.
   *
   * This prevents any bugs around consumers calling
   * subscribe/unsubscribe in the middle of a dispatch.
   */

  function ensureCanMutateNextListeners() {
    if (nextListeners === currentListeners) {
      nextListeners = currentListeners.slice();
    }
  }
  /**
   * Reads the state tree managed by the store.
   *
   * @returns {any} The current state tree of your application.
   */


  function getState() {
    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(3) : undefined);
    }

    return currentState;
  }
  /**
   * Adds a change listener. It will be called any time an action is dispatched,
   * and some part of the state tree may potentially have changed. You may then
   * call `getState()` to read the current state tree inside the callback.
   *
   * You may call `dispatch()` from a change listener, with the following
   * caveats:
   *
   * 1. The subscriptions are snapshotted just before every `dispatch()` call.
   * If you subscribe or unsubscribe while the listeners are being invoked, this
   * will not have any effect on the `dispatch()` that is currently in progress.
   * However, the next `dispatch()` call, whether nested or not, will use a more
   * recent snapshot of the subscription list.
   *
   * 2. The listener should not expect to see all state changes, as the state
   * might have been updated multiple times during a nested `dispatch()` before
   * the listener is called. It is, however, guaranteed that all subscribers
   * registered before the `dispatch()` started will be called with the latest
   * state by the time it exits.
   *
   * @param {Function} listener A callback to be invoked on every dispatch.
   * @returns {Function} A function to remove this change listener.
   */


  function subscribe(listener) {
    if (typeof listener !== 'function') {
      throw new Error( true ? formatProdErrorMessage(4) : undefined);
    }

    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(5) : undefined);
    }

    var isSubscribed = true;
    ensureCanMutateNextListeners();
    nextListeners.push(listener);
    return function unsubscribe() {
      if (!isSubscribed) {
        return;
      }

      if (isDispatching) {
        throw new Error( true ? formatProdErrorMessage(6) : undefined);
      }

      isSubscribed = false;
      ensureCanMutateNextListeners();
      var index = nextListeners.indexOf(listener);
      nextListeners.splice(index, 1);
      currentListeners = null;
    };
  }
  /**
   * Dispatches an action. It is the only way to trigger a state change.
   *
   * The `reducer` function, used to create the store, will be called with the
   * current state tree and the given `action`. Its return value will
   * be considered the **next** state of the tree, and the change listeners
   * will be notified.
   *
   * The base implementation only supports plain object actions. If you want to
   * dispatch a Promise, an Observable, a thunk, or something else, you need to
   * wrap your store creating function into the corresponding middleware. For
   * example, see the documentation for the `redux-thunk` package. Even the
   * middleware will eventually dispatch plain object actions using this method.
   *
   * @param {Object} action A plain object representing “what changed”. It is
   * a good idea to keep actions serializable so you can record and replay user
   * sessions, or use the time travelling `redux-devtools`. An action must have
   * a `type` property which may not be `undefined`. It is a good idea to use
   * string constants for action types.
   *
   * @returns {Object} For convenience, the same action object you dispatched.
   *
   * Note that, if you use a custom middleware, it may wrap `dispatch()` to
   * return something else (for example, a Promise you can await).
   */


  function dispatch(action) {
    if (!isPlainObject(action)) {
      throw new Error( true ? formatProdErrorMessage(7) : undefined);
    }

    if (typeof action.type === 'undefined') {
      throw new Error( true ? formatProdErrorMessage(8) : undefined);
    }

    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(9) : undefined);
    }

    try {
      isDispatching = true;
      currentState = currentReducer(currentState, action);
    } finally {
      isDispatching = false;
    }

    var listeners = currentListeners = nextListeners;

    for (var i = 0; i < listeners.length; i++) {
      var listener = listeners[i];
      listener();
    }

    return action;
  }
  /**
   * Replaces the reducer currently used by the store to calculate the state.
   *
   * You might need this if your app implements code splitting and you want to
   * load some of the reducers dynamically. You might also need this if you
   * implement a hot reloading mechanism for Redux.
   *
   * @param {Function} nextReducer The reducer for the store to use instead.
   * @returns {void}
   */


  function replaceReducer(nextReducer) {
    if (typeof nextReducer !== 'function') {
      throw new Error( true ? formatProdErrorMessage(10) : undefined);
    }

    currentReducer = nextReducer; // This action has a similiar effect to ActionTypes.INIT.
    // Any reducers that existed in both the new and old rootReducer
    // will receive the previous state. This effectively populates
    // the new state tree with any relevant data from the old one.

    dispatch({
      type: ActionTypes.REPLACE
    });
  }
  /**
   * Interoperability point for observable/reactive libraries.
   * @returns {observable} A minimal observable of state changes.
   * For more information, see the observable proposal:
   * https://github.com/tc39/proposal-observable
   */


  function observable() {
    var _ref;

    var outerSubscribe = subscribe;
    return _ref = {
      /**
       * The minimal observable subscription method.
       * @param {Object} observer Any object that can be used as an observer.
       * The observer object should have a `next` method.
       * @returns {subscription} An object with an `unsubscribe` method that can
       * be used to unsubscribe the observable from the store, and prevent further
       * emission of values from the observable.
       */
      subscribe: function subscribe(observer) {
        if (typeof observer !== 'object' || observer === null) {
          throw new Error( true ? formatProdErrorMessage(11) : undefined);
        }

        function observeState() {
          if (observer.next) {
            observer.next(getState());
          }
        }

        observeState();
        var unsubscribe = outerSubscribe(observeState);
        return {
          unsubscribe: unsubscribe
        };
      }
    }, _ref[$$observable] = function () {
      return this;
    }, _ref;
  } // When a store is created, an "INIT" action is dispatched so that every
  // reducer returns their initial state. This effectively populates
  // the initial state tree.


  dispatch({
    type: ActionTypes.INIT
  });
  return _ref2 = {
    dispatch: dispatch,
    subscribe: subscribe,
    getState: getState,
    replaceReducer: replaceReducer
  }, _ref2[$$observable] = observable, _ref2;
}

/**
 * Prints a warning in the console if it exists.
 *
 * @param {String} message The warning message.
 * @returns {void}
 */
function warning(message) {
  /* eslint-disable no-console */
  if (typeof console !== 'undefined' && typeof console.error === 'function') {
    console.error(message);
  }
  /* eslint-enable no-console */


  try {
    // This error was thrown as a convenience so that if you enable
    // "break on all exceptions" in your console,
    // it would pause the execution at this line.
    throw new Error(message);
  } catch (e) {} // eslint-disable-line no-empty

}

function getUnexpectedStateShapeWarningMessage(inputState, reducers, action, unexpectedKeyCache) {
  var reducerKeys = Object.keys(reducers);
  var argumentName = action && action.type === ActionTypes.INIT ? 'preloadedState argument passed to createStore' : 'previous state received by the reducer';

  if (reducerKeys.length === 0) {
    return 'Store does not have a valid reducer. Make sure the argument passed ' + 'to combineReducers is an object whose values are reducers.';
  }

  if (!isPlainObject(inputState)) {
    return "The " + argumentName + " has unexpected type of \"" + kindOf(inputState) + "\". Expected argument to be an object with the following " + ("keys: \"" + reducerKeys.join('", "') + "\"");
  }

  var unexpectedKeys = Object.keys(inputState).filter(function (key) {
    return !reducers.hasOwnProperty(key) && !unexpectedKeyCache[key];
  });
  unexpectedKeys.forEach(function (key) {
    unexpectedKeyCache[key] = true;
  });
  if (action && action.type === ActionTypes.REPLACE) return;

  if (unexpectedKeys.length > 0) {
    return "Unexpected " + (unexpectedKeys.length > 1 ? 'keys' : 'key') + " " + ("\"" + unexpectedKeys.join('", "') + "\" found in " + argumentName + ". ") + "Expected to find one of the known reducer keys instead: " + ("\"" + reducerKeys.join('", "') + "\". Unexpected keys will be ignored.");
  }
}

function assertReducerShape(reducers) {
  Object.keys(reducers).forEach(function (key) {
    var reducer = reducers[key];
    var initialState = reducer(undefined, {
      type: ActionTypes.INIT
    });

    if (typeof initialState === 'undefined') {
      throw new Error( true ? formatProdErrorMessage(12) : undefined);
    }

    if (typeof reducer(undefined, {
      type: ActionTypes.PROBE_UNKNOWN_ACTION()
    }) === 'undefined') {
      throw new Error( true ? formatProdErrorMessage(13) : undefined);
    }
  });
}
/**
 * Turns an object whose values are different reducer functions, into a single
 * reducer function. It will call every child reducer, and gather their results
 * into a single state object, whose keys correspond to the keys of the passed
 * reducer functions.
 *
 * @param {Object} reducers An object whose values correspond to different
 * reducer functions that need to be combined into one. One handy way to obtain
 * it is to use ES6 `import * as reducers` syntax. The reducers may never return
 * undefined for any action. Instead, they should return their initial state
 * if the state passed to them was undefined, and the current state for any
 * unrecognized action.
 *
 * @returns {Function} A reducer function that invokes every reducer inside the
 * passed object, and builds a state object with the same shape.
 */


function combineReducers(reducers) {
  var reducerKeys = Object.keys(reducers);
  var finalReducers = {};

  for (var i = 0; i < reducerKeys.length; i++) {
    var key = reducerKeys[i];

    if (false) {}

    if (typeof reducers[key] === 'function') {
      finalReducers[key] = reducers[key];
    }
  }

  var finalReducerKeys = Object.keys(finalReducers); // This is used to make sure we don't warn about the same
  // keys multiple times.

  var unexpectedKeyCache;

  if (false) {}

  var shapeAssertionError;

  try {
    assertReducerShape(finalReducers);
  } catch (e) {
    shapeAssertionError = e;
  }

  return function combination(state, action) {
    if (state === void 0) {
      state = {};
    }

    if (shapeAssertionError) {
      throw shapeAssertionError;
    }

    if (false) { var warningMessage; }

    var hasChanged = false;
    var nextState = {};

    for (var _i = 0; _i < finalReducerKeys.length; _i++) {
      var _key = finalReducerKeys[_i];
      var reducer = finalReducers[_key];
      var previousStateForKey = state[_key];
      var nextStateForKey = reducer(previousStateForKey, action);

      if (typeof nextStateForKey === 'undefined') {
        var actionType = action && action.type;
        throw new Error( true ? formatProdErrorMessage(14) : undefined);
      }

      nextState[_key] = nextStateForKey;
      hasChanged = hasChanged || nextStateForKey !== previousStateForKey;
    }

    hasChanged = hasChanged || finalReducerKeys.length !== Object.keys(state).length;
    return hasChanged ? nextState : state;
  };
}

function bindActionCreator(actionCreator, dispatch) {
  return function () {
    return dispatch(actionCreator.apply(this, arguments));
  };
}
/**
 * Turns an object whose values are action creators, into an object with the
 * same keys, but with every function wrapped into a `dispatch` call so they
 * may be invoked directly. This is just a convenience method, as you can call
 * `store.dispatch(MyActionCreators.doSomething())` yourself just fine.
 *
 * For convenience, you can also pass an action creator as the first argument,
 * and get a dispatch wrapped function in return.
 *
 * @param {Function|Object} actionCreators An object whose values are action
 * creator functions. One handy way to obtain it is to use ES6 `import * as`
 * syntax. You may also pass a single function.
 *
 * @param {Function} dispatch The `dispatch` function available on your Redux
 * store.
 *
 * @returns {Function|Object} The object mimicking the original object, but with
 * every action creator wrapped into the `dispatch` call. If you passed a
 * function as `actionCreators`, the return value will also be a single
 * function.
 */


function bindActionCreators(actionCreators, dispatch) {
  if (typeof actionCreators === 'function') {
    return bindActionCreator(actionCreators, dispatch);
  }

  if (typeof actionCreators !== 'object' || actionCreators === null) {
    throw new Error( true ? formatProdErrorMessage(16) : undefined);
  }

  var boundActionCreators = {};

  for (var key in actionCreators) {
    var actionCreator = actionCreators[key];

    if (typeof actionCreator === 'function') {
      boundActionCreators[key] = bindActionCreator(actionCreator, dispatch);
    }
  }

  return boundActionCreators;
}

/**
 * Composes single-argument functions from right to left. The rightmost
 * function can take multiple arguments as it provides the signature for
 * the resulting composite function.
 *
 * @param {...Function} funcs The functions to compose.
 * @returns {Function} A function obtained by composing the argument functions
 * from right to left. For example, compose(f, g, h) is identical to doing
 * (...args) => f(g(h(...args))).
 */
function compose() {
  for (var _len = arguments.length, funcs = new Array(_len), _key = 0; _key < _len; _key++) {
    funcs[_key] = arguments[_key];
  }

  if (funcs.length === 0) {
    return function (arg) {
      return arg;
    };
  }

  if (funcs.length === 1) {
    return funcs[0];
  }

  return funcs.reduce(function (a, b) {
    return function () {
      return a(b.apply(void 0, arguments));
    };
  });
}

/**
 * Creates a store enhancer that applies middleware to the dispatch method
 * of the Redux store. This is handy for a variety of tasks, such as expressing
 * asynchronous actions in a concise manner, or logging every action payload.
 *
 * See `redux-thunk` package as an example of the Redux middleware.
 *
 * Because middleware is potentially asynchronous, this should be the first
 * store enhancer in the composition chain.
 *
 * Note that each middleware will be given the `dispatch` and `getState` functions
 * as named arguments.
 *
 * @param {...Function} middlewares The middleware chain to be applied.
 * @returns {Function} A store enhancer applying the middleware.
 */

function applyMiddleware() {
  for (var _len = arguments.length, middlewares = new Array(_len), _key = 0; _key < _len; _key++) {
    middlewares[_key] = arguments[_key];
  }

  return function (createStore) {
    return function () {
      var store = createStore.apply(void 0, arguments);

      var _dispatch = function dispatch() {
        throw new Error( true ? formatProdErrorMessage(15) : undefined);
      };

      var middlewareAPI = {
        getState: store.getState,
        dispatch: function dispatch() {
          return _dispatch.apply(void 0, arguments);
        }
      };
      var chain = middlewares.map(function (middleware) {
        return middleware(middlewareAPI);
      });
      _dispatch = compose.apply(void 0, chain)(store.dispatch);
      return _objectSpread2(_objectSpread2({}, store), {}, {
        dispatch: _dispatch
      });
    };
  };
}

/*
 * This is a dummy function to check if the function name has been altered by minification.
 * If the function has been minified and NODE_ENV !== 'production', warn the user.
 */

function isCrushed() {}

if (false) {}



// CONCATENATED MODULE: ./node_modules/redux-thunk/es/index.js
/** A function that accepts a potential "extra argument" value to be injected later,
 * and returns an instance of the thunk middleware that uses that value
 */
function createThunkMiddleware(extraArgument) {
  // Standard Redux middleware definition pattern:
  // See: https://redux.js.org/tutorials/fundamentals/part-4-store#writing-custom-middleware
  var middleware = function middleware(_ref) {
    var dispatch = _ref.dispatch,
        getState = _ref.getState;
    return function (next) {
      return function (action) {
        // The thunk middleware looks for any functions that were passed to `store.dispatch`.
        // If this "action" is really a function, call it and return the result.
        if (typeof action === 'function') {
          // Inject the store's `dispatch` and `getState` methods, as well as any "extra arg"
          return action(dispatch, getState, extraArgument);
        } // Otherwise, pass the action down the middleware chain as usual


        return next(action);
      };
    };
  };

  return middleware;
}

var es_thunk = createThunkMiddleware(); // Attach the factory function so users can create a customized version
// with whatever "extra arg" they want to inject into their thunks

es_thunk.withExtraArgument = createThunkMiddleware;
/* harmony default export */ var es = (es_thunk);
// CONCATENATED MODULE: ./node_modules/@reduxjs/toolkit/dist/redux-toolkit.esm.js
var __extends = (undefined && undefined.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArray = (undefined && undefined.__spreadArray) || function (to, from) {
    for (var i = 0, il = from.length, j = to.length; i < il; i++, j++)
        to[j] = from[i];
    return to;
};
var __defProp = Object.defineProperty;
var __defProps = Object.defineProperties;
var __getOwnPropDescs = Object.getOwnPropertyDescriptors;
var __getOwnPropSymbols = Object.getOwnPropertySymbols;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __propIsEnum = Object.prototype.propertyIsEnumerable;
var __defNormalProp = function (obj, key, value) { return key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value: value }) : obj[key] = value; };
var __spreadValues = function (a, b) {
    for (var prop in b || (b = {}))
        if (__hasOwnProp.call(b, prop))
            __defNormalProp(a, prop, b[prop]);
    if (__getOwnPropSymbols)
        for (var _i = 0, _b = __getOwnPropSymbols(b); _i < _b.length; _i++) {
            var prop = _b[_i];
            if (__propIsEnum.call(b, prop))
                __defNormalProp(a, prop, b[prop]);
        }
    return a;
};
var __spreadProps = function (a, b) { return __defProps(a, __getOwnPropDescs(b)); };
var __async = function (__this, __arguments, generator) {
    return new Promise(function (resolve, reject) {
        var fulfilled = function (value) {
            try {
                step(generator.next(value));
            }
            catch (e) {
                reject(e);
            }
        };
        var rejected = function (value) {
            try {
                step(generator.throw(value));
            }
            catch (e) {
                reject(e);
            }
        };
        var step = function (x) { return x.done ? resolve(x.value) : Promise.resolve(x.value).then(fulfilled, rejected); };
        step((generator = generator.apply(__this, __arguments)).next());
    });
};
// src/index.ts




// src/createDraftSafeSelector.ts


var createDraftSafeSelector = function () {
    var args = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
    }
    var selector = createSelector.apply(void 0, args);
    var wrappedSelector = function (value) {
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            rest[_i - 1] = arguments[_i];
        }
        return selector.apply(void 0, __spreadArray([t(value) ? D(value) : value], rest));
    };
    return wrappedSelector;
};
// src/configureStore.ts

// src/devtoolsExtension.ts

var composeWithDevTools = typeof window !== "undefined" && window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ ? window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ : function () {
    if (arguments.length === 0)
        return void 0;
    if (typeof arguments[0] === "object")
        return compose;
    return compose.apply(null, arguments);
};
var devToolsEnhancer = typeof window !== "undefined" && window.__REDUX_DEVTOOLS_EXTENSION__ ? window.__REDUX_DEVTOOLS_EXTENSION__ : function () {
    return function (noop) {
        return noop;
    };
};
// src/isPlainObject.ts
function redux_toolkit_esm_isPlainObject(value) {
    if (typeof value !== "object" || value === null)
        return false;
    var proto = value;
    while (Object.getPrototypeOf(proto) !== null) {
        proto = Object.getPrototypeOf(proto);
    }
    return Object.getPrototypeOf(value) === proto;
}
// src/getDefaultMiddleware.ts

// src/utils.ts
function getTimeMeasureUtils(maxDelay, fnName) {
    var elapsed = 0;
    return {
        measureTime: function (fn) {
            var started = Date.now();
            try {
                return fn();
            }
            finally {
                var finished = Date.now();
                elapsed += finished - started;
            }
        },
        warnIfExceeded: function () {
            if (elapsed > maxDelay) {
                console.warn(fnName + " took " + elapsed + "ms, which is more than the warning threshold of " + maxDelay + "ms. \nIf your state or actions are very large, you may want to disable the middleware as it might cause too much of a slowdown in development mode. See https://redux-toolkit.js.org/api/getDefaultMiddleware for instructions.\nIt is disabled in production builds, so you don't need to worry about that.");
            }
        }
    };
}
var MiddlewareArray = /** @class */ (function (_super) {
    __extends(MiddlewareArray, _super);
    function MiddlewareArray() {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            args[_i] = arguments[_i];
        }
        var _this = _super.apply(this, args) || this;
        Object.setPrototypeOf(_this, MiddlewareArray.prototype);
        return _this;
    }
    Object.defineProperty(MiddlewareArray, Symbol.species, {
        get: function () {
            return MiddlewareArray;
        },
        enumerable: false,
        configurable: true
    });
    MiddlewareArray.prototype.concat = function () {
        var arr = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            arr[_i] = arguments[_i];
        }
        return _super.prototype.concat.apply(this, arr);
    };
    MiddlewareArray.prototype.prepend = function () {
        var arr = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            arr[_i] = arguments[_i];
        }
        if (arr.length === 1 && Array.isArray(arr[0])) {
            return new (MiddlewareArray.bind.apply(MiddlewareArray, __spreadArray([void 0], arr[0].concat(this))))();
        }
        return new (MiddlewareArray.bind.apply(MiddlewareArray, __spreadArray([void 0], arr.concat(this))))();
    };
    return MiddlewareArray;
}(Array));
// src/immutableStateInvariantMiddleware.ts
var isProduction = "production" === "production";
var prefix = "Invariant failed";
function invariant(condition, message) {
    if (condition) {
        return;
    }
    if (isProduction) {
        throw new Error(prefix);
    }
    throw new Error(prefix + ": " + (message || ""));
}
function stringify(obj, serializer, indent, decycler) {
    return JSON.stringify(obj, getSerialize(serializer, decycler), indent);
}
function getSerialize(serializer, decycler) {
    var stack = [], keys = [];
    if (!decycler)
        decycler = function (_, value) {
            if (stack[0] === value)
                return "[Circular ~]";
            return "[Circular ~." + keys.slice(0, stack.indexOf(value)).join(".") + "]";
        };
    return function (key, value) {
        if (stack.length > 0) {
            var thisPos = stack.indexOf(this);
            ~thisPos ? stack.splice(thisPos + 1) : stack.push(this);
            ~thisPos ? keys.splice(thisPos, Infinity, key) : keys.push(key);
            if (~stack.indexOf(value))
                value = decycler.call(this, key, value);
        }
        else
            stack.push(value);
        return serializer == null ? value : serializer.call(this, key, value);
    };
}
function isImmutableDefault(value) {
    return typeof value !== "object" || value === null || typeof value === "undefined" || Object.isFrozen(value);
}
function trackForMutations(isImmutable, ignorePaths, obj) {
    var trackedProperties = trackProperties(isImmutable, ignorePaths, obj);
    return {
        detectMutations: function () {
            return detectMutations(isImmutable, ignorePaths, trackedProperties, obj);
        }
    };
}
function trackProperties(isImmutable, ignorePaths, obj, path) {
    if (ignorePaths === void 0) { ignorePaths = []; }
    if (path === void 0) { path = ""; }
    var tracked = { value: obj };
    if (!isImmutable(obj)) {
        tracked.children = {};
        for (var key in obj) {
            var childPath = path ? path + "." + key : key;
            if (ignorePaths.length && ignorePaths.indexOf(childPath) !== -1) {
                continue;
            }
            tracked.children[key] = trackProperties(isImmutable, ignorePaths, obj[key], childPath);
        }
    }
    return tracked;
}
function detectMutations(isImmutable, ignorePaths, trackedProperty, obj, sameParentRef, path) {
    if (ignorePaths === void 0) { ignorePaths = []; }
    if (sameParentRef === void 0) { sameParentRef = false; }
    if (path === void 0) { path = ""; }
    var prevObj = trackedProperty ? trackedProperty.value : void 0;
    var sameRef = prevObj === obj;
    if (sameParentRef && !sameRef && !Number.isNaN(obj)) {
        return { wasMutated: true, path: path };
    }
    if (isImmutable(prevObj) || isImmutable(obj)) {
        return { wasMutated: false };
    }
    var keysToDetect = {};
    for (var key in trackedProperty.children) {
        keysToDetect[key] = true;
    }
    for (var key in obj) {
        keysToDetect[key] = true;
    }
    for (var key in keysToDetect) {
        var childPath = path ? path + "." + key : key;
        if (ignorePaths.length && ignorePaths.indexOf(childPath) !== -1) {
            continue;
        }
        var result = detectMutations(isImmutable, ignorePaths, trackedProperty.children[key], obj[key], sameRef, childPath);
        if (result.wasMutated) {
            return result;
        }
    }
    return { wasMutated: false };
}
function createImmutableStateInvariantMiddleware(options) {
    if (options === void 0) { options = {}; }
    if (true) {
        return function () { return function (next) { return function (action) { return next(action); }; }; };
    }
    var _b = options.isImmutable, isImmutable = _b === void 0 ? isImmutableDefault : _b, ignoredPaths = options.ignoredPaths, _c = options.warnAfter, warnAfter = _c === void 0 ? 32 : _c, ignore = options.ignore;
    ignoredPaths = ignoredPaths || ignore;
    var track = trackForMutations.bind(null, isImmutable, ignoredPaths);
    return function (_b) {
        var getState = _b.getState;
        var state = getState();
        var tracker = track(state);
        var result;
        return function (next) { return function (action) {
            var measureUtils = getTimeMeasureUtils(warnAfter, "ImmutableStateInvariantMiddleware");
            measureUtils.measureTime(function () {
                state = getState();
                result = tracker.detectMutations();
                tracker = track(state);
                invariant(!result.wasMutated, "A state mutation was detected between dispatches, in the path '" + (result.path || "") + "'.  This may cause incorrect behavior. (https://redux.js.org/style-guide/style-guide#do-not-mutate-state)");
            });
            var dispatchedAction = next(action);
            measureUtils.measureTime(function () {
                state = getState();
                result = tracker.detectMutations();
                tracker = track(state);
                result.wasMutated && invariant(!result.wasMutated, "A state mutation was detected inside a dispatch, in the path: " + (result.path || "") + ". Take a look at the reducer(s) handling the action " + stringify(action) + ". (https://redux.js.org/style-guide/style-guide#do-not-mutate-state)");
            });
            measureUtils.warnIfExceeded();
            return dispatchedAction;
        }; };
    };
}
// src/serializableStateInvariantMiddleware.ts
function isPlain(val) {
    var type = typeof val;
    return type === "undefined" || val === null || type === "string" || type === "boolean" || type === "number" || Array.isArray(val) || redux_toolkit_esm_isPlainObject(val);
}
function findNonSerializableValue(value, path, isSerializable, getEntries, ignoredPaths) {
    if (path === void 0) { path = ""; }
    if (isSerializable === void 0) { isSerializable = isPlain; }
    if (ignoredPaths === void 0) { ignoredPaths = []; }
    var foundNestedSerializable;
    if (!isSerializable(value)) {
        return {
            keyPath: path || "<root>",
            value: value
        };
    }
    if (typeof value !== "object" || value === null) {
        return false;
    }
    var entries = getEntries != null ? getEntries(value) : Object.entries(value);
    var hasIgnoredPaths = ignoredPaths.length > 0;
    for (var _i = 0, entries_1 = entries; _i < entries_1.length; _i++) {
        var _b = entries_1[_i], key = _b[0], nestedValue = _b[1];
        var nestedPath = path ? path + "." + key : key;
        if (hasIgnoredPaths && ignoredPaths.indexOf(nestedPath) >= 0) {
            continue;
        }
        if (!isSerializable(nestedValue)) {
            return {
                keyPath: nestedPath,
                value: nestedValue
            };
        }
        if (typeof nestedValue === "object") {
            foundNestedSerializable = findNonSerializableValue(nestedValue, nestedPath, isSerializable, getEntries, ignoredPaths);
            if (foundNestedSerializable) {
                return foundNestedSerializable;
            }
        }
    }
    return false;
}
function createSerializableStateInvariantMiddleware(options) {
    if (options === void 0) { options = {}; }
    if (true) {
        return function () { return function (next) { return function (action) { return next(action); }; }; };
    }
    var _b = options.isSerializable, isSerializable = _b === void 0 ? isPlain : _b, getEntries = options.getEntries, _c = options.ignoredActions, ignoredActions = _c === void 0 ? [] : _c, _d = options.ignoredActionPaths, ignoredActionPaths = _d === void 0 ? ["meta.arg", "meta.baseQueryMeta"] : _d, _e = options.ignoredPaths, ignoredPaths = _e === void 0 ? [] : _e, _f = options.warnAfter, warnAfter = _f === void 0 ? 32 : _f, _g = options.ignoreState, ignoreState = _g === void 0 ? false : _g;
    return function (storeAPI) { return function (next) { return function (action) {
        if (ignoredActions.length && ignoredActions.indexOf(action.type) !== -1) {
            return next(action);
        }
        var measureUtils = getTimeMeasureUtils(warnAfter, "SerializableStateInvariantMiddleware");
        measureUtils.measureTime(function () {
            var foundActionNonSerializableValue = findNonSerializableValue(action, "", isSerializable, getEntries, ignoredActionPaths);
            if (foundActionNonSerializableValue) {
                var keyPath = foundActionNonSerializableValue.keyPath, value = foundActionNonSerializableValue.value;
                console.error("A non-serializable value was detected in an action, in the path: `" + keyPath + "`. Value:", value, "\nTake a look at the logic that dispatched this action: ", action, "\n(See https://redux.js.org/faq/actions#why-should-type-be-a-string-or-at-least-serializable-why-should-my-action-types-be-constants)", "\n(To allow non-serializable values see: https://redux-toolkit.js.org/usage/usage-guide#working-with-non-serializable-data)");
            }
        });
        var result = next(action);
        if (!ignoreState) {
            measureUtils.measureTime(function () {
                var state = storeAPI.getState();
                var foundStateNonSerializableValue = findNonSerializableValue(state, "", isSerializable, getEntries, ignoredPaths);
                if (foundStateNonSerializableValue) {
                    var keyPath = foundStateNonSerializableValue.keyPath, value = foundStateNonSerializableValue.value;
                    console.error("A non-serializable value was detected in the state, in the path: `" + keyPath + "`. Value:", value, "\nTake a look at the reducer(s) handling this action type: " + action.type + ".\n(See https://redux.js.org/faq/organizing-state#can-i-put-functions-promises-or-other-non-serializable-items-in-my-store-state)");
                }
            });
            measureUtils.warnIfExceeded();
        }
        return result;
    }; }; };
}
// src/getDefaultMiddleware.ts
function isBoolean(x) {
    return typeof x === "boolean";
}
function curryGetDefaultMiddleware() {
    return function curriedGetDefaultMiddleware(options) {
        return getDefaultMiddleware(options);
    };
}
function getDefaultMiddleware(options) {
    if (options === void 0) { options = {}; }
    var _b = options.thunk, thunk = _b === void 0 ? true : _b, _c = options.immutableCheck, immutableCheck = _c === void 0 ? true : _c, _d = options.serializableCheck, serializableCheck = _d === void 0 ? true : _d;
    var middlewareArray = new MiddlewareArray();
    if (thunk) {
        if (isBoolean(thunk)) {
            middlewareArray.push(es);
        }
        else {
            middlewareArray.push(es.withExtraArgument(thunk.extraArgument));
        }
    }
    if (false) { var serializableOptions, immutableOptions; }
    return middlewareArray;
}
// src/configureStore.ts
var IS_PRODUCTION = "production" === "production";
function configureStore(options) {
    var curriedGetDefaultMiddleware = curryGetDefaultMiddleware();
    var _b = options || {}, _c = _b.reducer, reducer = _c === void 0 ? void 0 : _c, _d = _b.middleware, middleware = _d === void 0 ? curriedGetDefaultMiddleware() : _d, _e = _b.devTools, devTools = _e === void 0 ? true : _e, _f = _b.preloadedState, preloadedState = _f === void 0 ? void 0 : _f, _g = _b.enhancers, enhancers = _g === void 0 ? void 0 : _g;
    var rootReducer;
    if (typeof reducer === "function") {
        rootReducer = reducer;
    }
    else if (redux_toolkit_esm_isPlainObject(reducer)) {
        rootReducer = combineReducers(reducer);
    }
    else {
        throw new Error('"reducer" is a required argument, and must be a function or an object of functions that can be passed to combineReducers');
    }
    var finalMiddleware = middleware;
    if (typeof finalMiddleware === "function") {
        finalMiddleware = finalMiddleware(curriedGetDefaultMiddleware);
        if (!IS_PRODUCTION && !Array.isArray(finalMiddleware)) {
            throw new Error("when using a middleware builder function, an array of middleware must be returned");
        }
    }
    if (!IS_PRODUCTION && finalMiddleware.some(function (item) { return typeof item !== "function"; })) {
        throw new Error("each middleware provided to configureStore must be a function");
    }
    var middlewareEnhancer = applyMiddleware.apply(void 0, finalMiddleware);
    var finalCompose = compose;
    if (devTools) {
        finalCompose = composeWithDevTools(__spreadValues({
            trace: !IS_PRODUCTION
        }, typeof devTools === "object" && devTools));
    }
    var storeEnhancers = [middlewareEnhancer];
    if (Array.isArray(enhancers)) {
        storeEnhancers = __spreadArray([middlewareEnhancer], enhancers);
    }
    else if (typeof enhancers === "function") {
        storeEnhancers = enhancers(storeEnhancers);
    }
    var composedEnhancer = finalCompose.apply(void 0, storeEnhancers);
    return redux_createStore(rootReducer, preloadedState, composedEnhancer);
}
// src/createAction.ts
function createAction(type, prepareAction) {
    function actionCreator() {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            args[_i] = arguments[_i];
        }
        if (prepareAction) {
            var prepared = prepareAction.apply(void 0, args);
            if (!prepared) {
                throw new Error("prepareAction did not return an object");
            }
            return __spreadValues(__spreadValues({
                type: type,
                payload: prepared.payload
            }, "meta" in prepared && { meta: prepared.meta }), "error" in prepared && { error: prepared.error });
        }
        return { type: type, payload: args[0] };
    }
    actionCreator.toString = function () { return "" + type; };
    actionCreator.type = type;
    actionCreator.match = function (action) { return action.type === type; };
    return actionCreator;
}
function isFSA(action) {
    return redux_toolkit_esm_isPlainObject(action) && typeof action.type === "string" && Object.keys(action).every(isValidKey);
}
function isValidKey(key) {
    return ["type", "payload", "error", "meta"].indexOf(key) > -1;
}
function getType(actionCreator) {
    return "" + actionCreator;
}
// src/createReducer.ts

// src/mapBuilders.ts
function executeReducerBuilderCallback(builderCallback) {
    var actionsMap = {};
    var actionMatchers = [];
    var defaultCaseReducer;
    var builder = {
        addCase: function (typeOrActionCreator, reducer) {
            if (false) {}
            var type = typeof typeOrActionCreator === "string" ? typeOrActionCreator : typeOrActionCreator.type;
            if (type in actionsMap) {
                throw new Error("addCase cannot be called with two reducers for the same action type");
            }
            actionsMap[type] = reducer;
            return builder;
        },
        addMatcher: function (matcher, reducer) {
            if (false) {}
            actionMatchers.push({ matcher: matcher, reducer: reducer });
            return builder;
        },
        addDefaultCase: function (reducer) {
            if (false) {}
            defaultCaseReducer = reducer;
            return builder;
        }
    };
    builderCallback(builder);
    return [actionsMap, actionMatchers, defaultCaseReducer];
}
// src/createReducer.ts
function createReducer(initialState, mapOrBuilderCallback, actionMatchers, defaultCaseReducer) {
    if (actionMatchers === void 0) { actionMatchers = []; }
    var _b = typeof mapOrBuilderCallback === "function" ? executeReducerBuilderCallback(mapOrBuilderCallback) : [mapOrBuilderCallback, actionMatchers, defaultCaseReducer], actionsMap = _b[0], finalActionMatchers = _b[1], finalDefaultCaseReducer = _b[2];
    var frozenInitialState = immer_esm(initialState, function () {
    });
    return function (state, action) {
        if (state === void 0) { state = frozenInitialState; }
        var caseReducers = __spreadArray([
            actionsMap[action.type]
        ], finalActionMatchers.filter(function (_b) {
            var matcher = _b.matcher;
            return matcher(action);
        }).map(function (_b) {
            var reducer = _b.reducer;
            return reducer;
        }));
        if (caseReducers.filter(function (cr) { return !!cr; }).length === 0) {
            caseReducers = [finalDefaultCaseReducer];
        }
        return caseReducers.reduce(function (previousState, caseReducer) {
            if (caseReducer) {
                if (t(previousState)) {
                    var draft = previousState;
                    var result = caseReducer(draft, action);
                    if (typeof result === "undefined") {
                        return previousState;
                    }
                    return result;
                }
                else if (!r(previousState)) {
                    var result = caseReducer(previousState, action);
                    if (typeof result === "undefined") {
                        if (previousState === null) {
                            return previousState;
                        }
                        throw Error("A case reducer on a non-draftable value must not return undefined");
                    }
                    return result;
                }
                else {
                    return immer_esm(previousState, function (draft) {
                        return caseReducer(draft, action);
                    });
                }
            }
            return previousState;
        }, state);
    };
}
// src/createSlice.ts
function getType2(slice, actionKey) {
    return slice + "/" + actionKey;
}
function createSlice(options) {
    var name = options.name, initialState = options.initialState;
    if (!name) {
        throw new Error("`name` is a required option for createSlice");
    }
    var reducers = options.reducers || {};
    var _b = typeof options.extraReducers === "function" ? executeReducerBuilderCallback(options.extraReducers) : [options.extraReducers], _c = _b[0], extraReducers = _c === void 0 ? {} : _c, _d = _b[1], actionMatchers = _d === void 0 ? [] : _d, _e = _b[2], defaultCaseReducer = _e === void 0 ? void 0 : _e;
    var reducerNames = Object.keys(reducers);
    var sliceCaseReducersByName = {};
    var sliceCaseReducersByType = {};
    var actionCreators = {};
    reducerNames.forEach(function (reducerName) {
        var maybeReducerWithPrepare = reducers[reducerName];
        var type = getType2(name, reducerName);
        var caseReducer;
        var prepareCallback;
        if ("reducer" in maybeReducerWithPrepare) {
            caseReducer = maybeReducerWithPrepare.reducer;
            prepareCallback = maybeReducerWithPrepare.prepare;
        }
        else {
            caseReducer = maybeReducerWithPrepare;
        }
        sliceCaseReducersByName[reducerName] = caseReducer;
        sliceCaseReducersByType[type] = caseReducer;
        actionCreators[reducerName] = prepareCallback ? createAction(type, prepareCallback) : createAction(type);
    });
    var finalCaseReducers = __spreadValues(__spreadValues({}, extraReducers), sliceCaseReducersByType);
    var reducer = createReducer(initialState, finalCaseReducers, actionMatchers, defaultCaseReducer);
    return {
        name: name,
        reducer: reducer,
        actions: actionCreators,
        caseReducers: sliceCaseReducersByName
    };
}
// src/entities/entity_state.ts
function getInitialEntityState() {
    return {
        ids: [],
        entities: {}
    };
}
function createInitialStateFactory() {
    function getInitialState(additionalState) {
        if (additionalState === void 0) { additionalState = {}; }
        return Object.assign(getInitialEntityState(), additionalState);
    }
    return { getInitialState: getInitialState };
}
// src/entities/state_selectors.ts
function createSelectorsFactory() {
    function getSelectors(selectState) {
        var selectIds = function (state) { return state.ids; };
        var selectEntities = function (state) { return state.entities; };
        var selectAll = createDraftSafeSelector(selectIds, selectEntities, function (ids, entities) { return ids.map(function (id) { return entities[id]; }); });
        var selectId = function (_, id) { return id; };
        var selectById = function (entities, id) { return entities[id]; };
        var selectTotal = createDraftSafeSelector(selectIds, function (ids) { return ids.length; });
        if (!selectState) {
            return {
                selectIds: selectIds,
                selectEntities: selectEntities,
                selectAll: selectAll,
                selectTotal: selectTotal,
                selectById: createDraftSafeSelector(selectEntities, selectId, selectById)
            };
        }
        var selectGlobalizedEntities = createDraftSafeSelector(selectState, selectEntities);
        return {
            selectIds: createDraftSafeSelector(selectState, selectIds),
            selectEntities: selectGlobalizedEntities,
            selectAll: createDraftSafeSelector(selectState, selectAll),
            selectTotal: createDraftSafeSelector(selectState, selectTotal),
            selectById: createDraftSafeSelector(selectGlobalizedEntities, selectId, selectById)
        };
    }
    return { getSelectors: getSelectors };
}
// src/entities/state_adapter.ts

function createSingleArgumentStateOperator(mutator) {
    var operator = createStateOperator(function (_, state) { return mutator(state); });
    return function operation(state) {
        return operator(state, void 0);
    };
}
function createStateOperator(mutator) {
    return function operation(state, arg) {
        function isPayloadActionArgument(arg2) {
            return isFSA(arg2);
        }
        var runMutator = function (draft) {
            if (isPayloadActionArgument(arg)) {
                mutator(arg.payload, draft);
            }
            else {
                mutator(arg, draft);
            }
        };
        if (t(state)) {
            runMutator(state);
            return state;
        }
        else {
            return immer_esm(state, runMutator);
        }
    };
}
// src/entities/utils.ts
function selectIdValue(entity, selectId) {
    var key = selectId(entity);
    if (false) {}
    return key;
}
function ensureEntitiesArray(entities) {
    if (!Array.isArray(entities)) {
        entities = Object.values(entities);
    }
    return entities;
}
function splitAddedUpdatedEntities(newEntities, selectId, state) {
    newEntities = ensureEntitiesArray(newEntities);
    var added = [];
    var updated = [];
    for (var _i = 0, newEntities_1 = newEntities; _i < newEntities_1.length; _i++) {
        var entity = newEntities_1[_i];
        var id = selectIdValue(entity, selectId);
        if (id in state.entities) {
            updated.push({ id: id, changes: entity });
        }
        else {
            added.push(entity);
        }
    }
    return [added, updated];
}
// src/entities/unsorted_state_adapter.ts
function createUnsortedStateAdapter(selectId) {
    function addOneMutably(entity, state) {
        var key = selectIdValue(entity, selectId);
        if (key in state.entities) {
            return;
        }
        state.ids.push(key);
        state.entities[key] = entity;
    }
    function addManyMutably(newEntities, state) {
        newEntities = ensureEntitiesArray(newEntities);
        for (var _i = 0, newEntities_2 = newEntities; _i < newEntities_2.length; _i++) {
            var entity = newEntities_2[_i];
            addOneMutably(entity, state);
        }
    }
    function setOneMutably(entity, state) {
        var key = selectIdValue(entity, selectId);
        if (!(key in state.entities)) {
            state.ids.push(key);
        }
        state.entities[key] = entity;
    }
    function setManyMutably(newEntities, state) {
        newEntities = ensureEntitiesArray(newEntities);
        for (var _i = 0, newEntities_3 = newEntities; _i < newEntities_3.length; _i++) {
            var entity = newEntities_3[_i];
            setOneMutably(entity, state);
        }
    }
    function setAllMutably(newEntities, state) {
        newEntities = ensureEntitiesArray(newEntities);
        state.ids = [];
        state.entities = {};
        addManyMutably(newEntities, state);
    }
    function removeOneMutably(key, state) {
        return removeManyMutably([key], state);
    }
    function removeManyMutably(keys, state) {
        var didMutate = false;
        keys.forEach(function (key) {
            if (key in state.entities) {
                delete state.entities[key];
                didMutate = true;
            }
        });
        if (didMutate) {
            state.ids = state.ids.filter(function (id) { return id in state.entities; });
        }
    }
    function removeAllMutably(state) {
        Object.assign(state, {
            ids: [],
            entities: {}
        });
    }
    function takeNewKey(keys, update, state) {
        var original2 = state.entities[update.id];
        var updated = Object.assign({}, original2, update.changes);
        var newKey = selectIdValue(updated, selectId);
        var hasNewKey = newKey !== update.id;
        if (hasNewKey) {
            keys[update.id] = newKey;
            delete state.entities[update.id];
        }
        state.entities[newKey] = updated;
        return hasNewKey;
    }
    function updateOneMutably(update, state) {
        return updateManyMutably([update], state);
    }
    function updateManyMutably(updates, state) {
        var newKeys = {};
        var updatesPerEntity = {};
        updates.forEach(function (update) {
            if (update.id in state.entities) {
                updatesPerEntity[update.id] = {
                    id: update.id,
                    changes: __spreadValues(__spreadValues({}, updatesPerEntity[update.id] ? updatesPerEntity[update.id].changes : null), update.changes)
                };
            }
        });
        updates = Object.values(updatesPerEntity);
        var didMutateEntities = updates.length > 0;
        if (didMutateEntities) {
            var didMutateIds = updates.filter(function (update) { return takeNewKey(newKeys, update, state); }).length > 0;
            if (didMutateIds) {
                state.ids = state.ids.map(function (id) { return newKeys[id] || id; });
            }
        }
    }
    function upsertOneMutably(entity, state) {
        return upsertManyMutably([entity], state);
    }
    function upsertManyMutably(newEntities, state) {
        var _b = splitAddedUpdatedEntities(newEntities, selectId, state), added = _b[0], updated = _b[1];
        updateManyMutably(updated, state);
        addManyMutably(added, state);
    }
    return {
        removeAll: createSingleArgumentStateOperator(removeAllMutably),
        addOne: createStateOperator(addOneMutably),
        addMany: createStateOperator(addManyMutably),
        setOne: createStateOperator(setOneMutably),
        setMany: createStateOperator(setManyMutably),
        setAll: createStateOperator(setAllMutably),
        updateOne: createStateOperator(updateOneMutably),
        updateMany: createStateOperator(updateManyMutably),
        upsertOne: createStateOperator(upsertOneMutably),
        upsertMany: createStateOperator(upsertManyMutably),
        removeOne: createStateOperator(removeOneMutably),
        removeMany: createStateOperator(removeManyMutably)
    };
}
// src/entities/sorted_state_adapter.ts
function createSortedStateAdapter(selectId, sort) {
    var _b = createUnsortedStateAdapter(selectId), removeOne = _b.removeOne, removeMany = _b.removeMany, removeAll = _b.removeAll;
    function addOneMutably(entity, state) {
        return addManyMutably([entity], state);
    }
    function addManyMutably(newEntities, state) {
        newEntities = ensureEntitiesArray(newEntities);
        var models = newEntities.filter(function (model) { return !(selectIdValue(model, selectId) in state.entities); });
        if (models.length !== 0) {
            merge(models, state);
        }
    }
    function setOneMutably(entity, state) {
        return setManyMutably([entity], state);
    }
    function setManyMutably(newEntities, state) {
        newEntities = ensureEntitiesArray(newEntities);
        if (newEntities.length !== 0) {
            merge(newEntities, state);
        }
    }
    function setAllMutably(newEntities, state) {
        newEntities = ensureEntitiesArray(newEntities);
        state.entities = {};
        state.ids = [];
        addManyMutably(newEntities, state);
    }
    function updateOneMutably(update, state) {
        return updateManyMutably([update], state);
    }
    function takeUpdatedModel(models, update, state) {
        if (!(update.id in state.entities)) {
            return false;
        }
        var original2 = state.entities[update.id];
        var updated = Object.assign({}, original2, update.changes);
        var newKey = selectIdValue(updated, selectId);
        delete state.entities[update.id];
        models.push(updated);
        return newKey !== update.id;
    }
    function updateManyMutably(updates, state) {
        var models = [];
        updates.forEach(function (update) { return takeUpdatedModel(models, update, state); });
        if (models.length !== 0) {
            merge(models, state);
        }
    }
    function upsertOneMutably(entity, state) {
        return upsertManyMutably([entity], state);
    }
    function upsertManyMutably(newEntities, state) {
        var _b = splitAddedUpdatedEntities(newEntities, selectId, state), added = _b[0], updated = _b[1];
        updateManyMutably(updated, state);
        addManyMutably(added, state);
    }
    function areArraysEqual(a, b) {
        if (a.length !== b.length) {
            return false;
        }
        for (var i = 0; i < a.length && i < b.length; i++) {
            if (a[i] === b[i]) {
                continue;
            }
            return false;
        }
        return true;
    }
    function merge(models, state) {
        models.forEach(function (model) {
            state.entities[selectId(model)] = model;
        });
        var allEntities = Object.values(state.entities);
        allEntities.sort(sort);
        var newSortedIds = allEntities.map(selectId);
        var ids = state.ids;
        if (!areArraysEqual(ids, newSortedIds)) {
            state.ids = newSortedIds;
        }
    }
    return {
        removeOne: removeOne,
        removeMany: removeMany,
        removeAll: removeAll,
        addOne: createStateOperator(addOneMutably),
        updateOne: createStateOperator(updateOneMutably),
        upsertOne: createStateOperator(upsertOneMutably),
        setOne: createStateOperator(setOneMutably),
        setMany: createStateOperator(setManyMutably),
        setAll: createStateOperator(setAllMutably),
        addMany: createStateOperator(addManyMutably),
        updateMany: createStateOperator(updateManyMutably),
        upsertMany: createStateOperator(upsertManyMutably)
    };
}
// src/entities/create_adapter.ts
function createEntityAdapter(options) {
    if (options === void 0) { options = {}; }
    var _b = __spreadValues({
        sortComparer: false,
        selectId: function (instance) { return instance.id; }
    }, options), selectId = _b.selectId, sortComparer = _b.sortComparer;
    var stateFactory = createInitialStateFactory();
    var selectorsFactory = createSelectorsFactory();
    var stateAdapter = sortComparer ? createSortedStateAdapter(selectId, sortComparer) : createUnsortedStateAdapter(selectId);
    return __spreadValues(__spreadValues(__spreadValues({
        selectId: selectId,
        sortComparer: sortComparer
    }, stateFactory), selectorsFactory), stateAdapter);
}
// src/nanoid.ts
var urlAlphabet = "ModuleSymbhasOwnPr-0123456789ABCDEFGHNRVfgctiUvz_KqYTJkLxpZXIjQW";
var nanoid = function (size) {
    if (size === void 0) { size = 21; }
    var id = "";
    var i = size;
    while (i--) {
        id += urlAlphabet[Math.random() * 64 | 0];
    }
    return id;
};
// src/createAsyncThunk.ts
var commonProperties = [
    "name",
    "message",
    "stack",
    "code"
];
var RejectWithValue = /** @class */ (function () {
    function RejectWithValue(payload, meta) {
        this.payload = payload;
        this.meta = meta;
    }
    return RejectWithValue;
}());
var FulfillWithMeta = /** @class */ (function () {
    function FulfillWithMeta(payload, meta) {
        this.payload = payload;
        this.meta = meta;
    }
    return FulfillWithMeta;
}());
var miniSerializeError = function (value) {
    if (typeof value === "object" && value !== null) {
        var simpleError = {};
        for (var _i = 0, commonProperties_1 = commonProperties; _i < commonProperties_1.length; _i++) {
            var property = commonProperties_1[_i];
            if (typeof value[property] === "string") {
                simpleError[property] = value[property];
            }
        }
        return simpleError;
    }
    return { message: String(value) };
};
function createAsyncThunk(typePrefix, payloadCreator, options) {
    var fulfilled = createAction(typePrefix + "/fulfilled", function (payload, requestId, arg, meta) { return ({
        payload: payload,
        meta: __spreadProps(__spreadValues({}, meta || {}), {
            arg: arg,
            requestId: requestId,
            requestStatus: "fulfilled"
        })
    }); });
    var pending = createAction(typePrefix + "/pending", function (requestId, arg, meta) { return ({
        payload: void 0,
        meta: __spreadProps(__spreadValues({}, meta || {}), {
            arg: arg,
            requestId: requestId,
            requestStatus: "pending"
        })
    }); });
    var rejected = createAction(typePrefix + "/rejected", function (error, requestId, arg, payload, meta) { return ({
        payload: payload,
        error: (options && options.serializeError || miniSerializeError)(error || "Rejected"),
        meta: __spreadProps(__spreadValues({}, meta || {}), {
            arg: arg,
            requestId: requestId,
            rejectedWithValue: !!payload,
            requestStatus: "rejected",
            aborted: (error == null ? void 0 : error.name) === "AbortError",
            condition: (error == null ? void 0 : error.name) === "ConditionError"
        })
    }); });
    var displayedWarning = false;
    var AC = typeof AbortController !== "undefined" ? AbortController : /** @class */ (function () {
        function class_1() {
            this.signal = {
                aborted: false,
                addEventListener: function () {
                },
                dispatchEvent: function () {
                    return false;
                },
                onabort: function () {
                },
                removeEventListener: function () {
                }
            };
        }
        class_1.prototype.abort = function () {
            if (false) {}
        };
        return class_1;
    }());
    function actionCreator(arg) {
        return function (dispatch, getState, extra) {
            var _a;
            var requestId = ((_a = options == null ? void 0 : options.idGenerator) != null ? _a : nanoid)();
            var abortController = new AC();
            var abortReason;
            var abortedPromise = new Promise(function (_, reject) { return abortController.signal.addEventListener("abort", function () { return reject({ name: "AbortError", message: abortReason || "Aborted" }); }); });
            var started = false;
            function abort(reason) {
                if (started) {
                    abortReason = reason;
                    abortController.abort();
                }
            }
            var promise = function () {
                return __async(this, null, function () {
                    var _a2, finalAction, err_1, skipDispatch;
                    return __generator(this, function (_b) {
                        switch (_b.label) {
                            case 0:
                                _b.trys.push([0, 2, , 3]);
                                if (options && options.condition && options.condition(arg, { getState: getState, extra: extra }) === false) {
                                    throw {
                                        name: "ConditionError",
                                        message: "Aborted due to condition callback returning false."
                                    };
                                }
                                started = true;
                                dispatch(pending(requestId, arg, (_a2 = options == null ? void 0 : options.getPendingMeta) == null ? void 0 : _a2.call(options, { requestId: requestId, arg: arg }, { getState: getState, extra: extra })));
                                return [4 /*yield*/, Promise.race([
                                        abortedPromise,
                                        Promise.resolve(payloadCreator(arg, {
                                            dispatch: dispatch,
                                            getState: getState,
                                            extra: extra,
                                            requestId: requestId,
                                            signal: abortController.signal,
                                            rejectWithValue: function (value, meta) {
                                                return new RejectWithValue(value, meta);
                                            },
                                            fulfillWithValue: function (value, meta) {
                                                return new FulfillWithMeta(value, meta);
                                            }
                                        })).then(function (result) {
                                            if (result instanceof RejectWithValue) {
                                                throw result;
                                            }
                                            if (result instanceof FulfillWithMeta) {
                                                return fulfilled(result.payload, requestId, arg, result.meta);
                                            }
                                            return fulfilled(result, requestId, arg);
                                        })
                                    ])];
                            case 1:
                                finalAction = _b.sent();
                                return [3 /*break*/, 3];
                            case 2:
                                err_1 = _b.sent();
                                finalAction = err_1 instanceof RejectWithValue ? rejected(null, requestId, arg, err_1.payload, err_1.meta) : rejected(err_1, requestId, arg);
                                return [3 /*break*/, 3];
                            case 3:
                                skipDispatch = options && !options.dispatchConditionRejection && rejected.match(finalAction) && finalAction.meta.condition;
                                if (!skipDispatch) {
                                    dispatch(finalAction);
                                }
                                return [2 /*return*/, finalAction];
                        }
                    });
                });
            }();
            return Object.assign(promise, {
                abort: abort,
                requestId: requestId,
                arg: arg,
                unwrap: function () {
                    return promise.then(unwrapResult);
                }
            });
        };
    }
    return Object.assign(actionCreator, {
        pending: pending,
        rejected: rejected,
        fulfilled: fulfilled,
        typePrefix: typePrefix
    });
}
function unwrapResult(action) {
    if (action.meta && action.meta.rejectedWithValue) {
        throw action.payload;
    }
    if (action.error) {
        throw action.error;
    }
    return action.payload;
}
// src/tsHelpers.ts
var hasMatchFunction = function (v) {
    return v && typeof v.match === "function";
};
// src/matchers.ts
var matches = function (matcher, action) {
    if (hasMatchFunction(matcher)) {
        return matcher.match(action);
    }
    else {
        return matcher(action);
    }
};
function isAnyOf() {
    var matchers = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        matchers[_i] = arguments[_i];
    }
    return function (action) {
        return matchers.some(function (matcher) { return matches(matcher, action); });
    };
}
function isAllOf() {
    var matchers = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        matchers[_i] = arguments[_i];
    }
    return function (action) {
        return matchers.every(function (matcher) { return matches(matcher, action); });
    };
}
function hasExpectedRequestMetadata(action, validStatus) {
    if (!action || !action.meta)
        return false;
    var hasValidRequestId = typeof action.meta.requestId === "string";
    var hasValidRequestStatus = validStatus.indexOf(action.meta.requestStatus) > -1;
    return hasValidRequestId && hasValidRequestStatus;
}
function isAsyncThunkArray(a) {
    return typeof a[0] === "function" && "pending" in a[0] && "fulfilled" in a[0] && "rejected" in a[0];
}
function isPending() {
    var asyncThunks = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        asyncThunks[_i] = arguments[_i];
    }
    if (asyncThunks.length === 0) {
        return function (action) { return hasExpectedRequestMetadata(action, ["pending"]); };
    }
    if (!isAsyncThunkArray(asyncThunks)) {
        return isPending()(asyncThunks[0]);
    }
    return function (action) {
        var matchers = asyncThunks.map(function (asyncThunk) { return asyncThunk.pending; });
        var combinedMatcher = isAnyOf.apply(void 0, matchers);
        return combinedMatcher(action);
    };
}
function isRejected() {
    var asyncThunks = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        asyncThunks[_i] = arguments[_i];
    }
    if (asyncThunks.length === 0) {
        return function (action) { return hasExpectedRequestMetadata(action, ["rejected"]); };
    }
    if (!isAsyncThunkArray(asyncThunks)) {
        return isRejected()(asyncThunks[0]);
    }
    return function (action) {
        var matchers = asyncThunks.map(function (asyncThunk) { return asyncThunk.rejected; });
        var combinedMatcher = isAnyOf.apply(void 0, matchers);
        return combinedMatcher(action);
    };
}
function isRejectedWithValue() {
    var asyncThunks = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        asyncThunks[_i] = arguments[_i];
    }
    var hasFlag = function (action) {
        return action && action.meta && action.meta.rejectedWithValue;
    };
    if (asyncThunks.length === 0) {
        return function (action) {
            var combinedMatcher = isAllOf(isRejected.apply(void 0, asyncThunks), hasFlag);
            return combinedMatcher(action);
        };
    }
    if (!isAsyncThunkArray(asyncThunks)) {
        return isRejectedWithValue()(asyncThunks[0]);
    }
    return function (action) {
        var combinedMatcher = isAllOf(isRejected.apply(void 0, asyncThunks), hasFlag);
        return combinedMatcher(action);
    };
}
function isFulfilled() {
    var asyncThunks = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        asyncThunks[_i] = arguments[_i];
    }
    if (asyncThunks.length === 0) {
        return function (action) { return hasExpectedRequestMetadata(action, ["fulfilled"]); };
    }
    if (!isAsyncThunkArray(asyncThunks)) {
        return isFulfilled()(asyncThunks[0]);
    }
    return function (action) {
        var matchers = asyncThunks.map(function (asyncThunk) { return asyncThunk.fulfilled; });
        var combinedMatcher = isAnyOf.apply(void 0, matchers);
        return combinedMatcher(action);
    };
}
function isAsyncThunkAction() {
    var asyncThunks = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        asyncThunks[_i] = arguments[_i];
    }
    if (asyncThunks.length === 0) {
        return function (action) { return hasExpectedRequestMetadata(action, ["pending", "fulfilled", "rejected"]); };
    }
    if (!isAsyncThunkArray(asyncThunks)) {
        return isAsyncThunkAction()(asyncThunks[0]);
    }
    return function (action) {
        var matchers = [];
        for (var _i = 0, asyncThunks_1 = asyncThunks; _i < asyncThunks_1.length; _i++) {
            var asyncThunk = asyncThunks_1[_i];
            matchers.push(asyncThunk.pending, asyncThunk.rejected, asyncThunk.fulfilled);
        }
        var combinedMatcher = isAnyOf.apply(void 0, matchers);
        return combinedMatcher(action);
    };
}
// src/index.ts
N();

//# sourceMappingURL=redux-toolkit.esm.js.map
// CONCATENATED MODULE: ./production/src/main/js/domain/helper.ts
/**
 * Return true if param is an object.
 */
var isObject = function (obj) {
    return !!(obj && typeof obj === 'object' && !Array.isArray(obj) && Object.prototype.toString.call(obj) !== '[object Date]');
};
/**
 * performs a deep merge of objects and returns new object.
 * does not modify objects (immutable).
 */
var deepMerge = function () {
    var objects = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        objects[_i] = arguments[_i];
    }
    return objects.reduce(function (prev, obj) {
        // loop through object properties
        for (var _i = 0, _a = Object.entries(obj); _i < _a.length; _i++) {
            var _b = _a[_i], key = _b[0], value = _b[1];
            var prevValue = prev[key];
            // if properties are objects
            if (isObject(prevValue) && isObject(value)) {
                // merge them recursively
                prev[key] = deepMerge(prevValue, value);
            }
            else {
                prev[key] = value;
            }
        }
        return prev;
    }, {});
};
/**
 * check is string is numeric
 */
var isNumeric = function (str) {
    return !isNaN(parseFloat(str)) && isFinite(str);
};
/**
 * get actions by the specified type, for example, 'sort' actions
 * the function returns all types that CONTAIN the given type, for example 'my-sort', 'sort-new' etc. also will be returned
 */
var getActionsByType = function (actions, type) {
    var _a;
    var filtered = [];
    for (var _i = 0, _b = Object.values(actions); _i < _b.length; _i++) {
        var action = _b[_i];
        if (((_a = action.control) === null || _a === void 0 ? void 0 : _a.indexOf(type)) !== -1) {
            filtered.push(action);
        }
    }
    return filtered;
};
/**
 * scroll or jump to the top
 */
var scrollTop = function () {
    window.scroll(0, 0);
    /*
    @media (prefers-reduced-motion: no-preference)
    :root {
        scroll-behavior: smooth;
    }*/
};
var isWhiteSpace = function (text) {
    return text.trim().length === 0;
};

// CONCATENATED MODULE: ./production/src/main/js/data/storage.ts
/**
 * check if storage type is enabled
 * @param storageType - 'local-storage', 'session-storage', or 'cookies'
 */
var isStorageEnabled = function (storageType) {
    if (storageType === 'cookies')
        return true;
    if (storageType === 'local-storage') {
        var temp = 'test';
        try {
            window.localStorage.setItem(temp, temp);
            window.localStorage.removeItem(temp);
            return true;
        }
        catch (e) {
            return false;
        }
    }
    if (storageType === 'session-storage') {
        var temp = 'test';
        try {
            window.sessionStorage.setItem(temp, temp);
            window.sessionStorage.removeItem(temp);
            return true;
        }
        catch (e) {
            return false;
        }
    }
    return false;
};
/**
 * set Cookie
 * @param name - cookie name
 * @param value - cookie value
 * @param expiration - cookie expiration in minutes (-1 = cookie expires when browser is closed)
 * @param secure
 * @param samesite - strict or lax
 */
var setCookie = function (name, value, expiration, secure, samesite) {
    if (expiration === void 0) { expiration = -1; }
    if (secure === void 0) { secure = false; }
    if (samesite === void 0) { samesite = undefined; }
    var escapedValue = encodeURIComponent(typeof value === 'object' ? JSON.stringify(value) : value);
    var exp = Number(expiration) || -1;
    var cookieString = '';
    if (exp === -1) {
        // cookie expires when browser is closed
        // If neither expires nor max-age specified the cookie will expire at the end of session.
        cookieString = name + "=" + escapedValue + ";path=/;";
    }
    else {
        var date = new Date();
        date.setMinutes(date.getMinutes() + expiration);
        // option to set expiration in days:
        //864e5 = 86400000 = 1000*60*60*24 represents the number of milliseconds in a 24 hour day.
        //date.setTime(date.getTime() + (days * 864e5));
        var expires = date.toUTCString();
        cookieString = name + "=" + escapedValue + ";path=/; expires=" + expires;
    }
    if (samesite) {
        // The strict value will prevent the cookie from being sent by the browser to the target site in all cross-site browsing context, even when following a regular link.
        // The lax value will only send cookies for TOP LEVEL navigation GET requests. This is sufficient for user tracking, but it will prevent many CSRF attacks.
        cookieString += ";samesite=" + samesite;
    }
    if (secure) {
        // Cookie to only be transmitted over secure protocol as https. Before Chrome 52, this flag could appear with cookies from http domains.
        cookieString += ';secure';
    }
    document.cookie = cookieString;
};
/**
 * get cookie by name
 */
var getCookie = function (name) {
    var cookies = document.cookie.split(';');
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var index = cookie.indexOf('=');
        var key = cookie.substr(0, index);
        var value = cookie.substr(index + 1);
        if (key.trim().toLowerCase() === name.trim().toLowerCase()) {
            return decodeURIComponent(value);
        }
    }
    return null;
};
/**
 * save a value to the local storage
 */
var setLocalStorageItem = function (storageName, value) {
    var escapedValue = encodeURIComponent(typeof value === 'object' ? JSON.stringify(value) : value);
    window.localStorage.setItem(storageName, escapedValue);
};
/**
 * get a value to the local storage
 * @param storageName
 */
var getLocalStorageItem = function (storageName) {
    var value = window.localStorage.getItem(storageName);
    return decodeURIComponent(value);
};
/**
 * save a value to the session storage
 */
var setSessionStorageItem = function (storageName, value) {
    var escapedValue = encodeURIComponent(typeof value === 'object' ? JSON.stringify(value) : value);
    window.sessionStorage.setItem(storageName, escapedValue);
};
/**
 * get a value to the session storage
 */
var getSessionStorageItem = function (storageName) {
    var value = window.sessionStorage.getItem(storageName);
    return decodeURIComponent(value);
};
/**
 * save to web storage or cookies
 * @param storageType - 'local-storage', 'session-storage', or 'cookies'
 * @param storageName - it is used like key name in web storage, or like cookie name
 * @param value - the value that should be stored
 * @param cookiesExpiration - cookies expiration in minutes (-1 = cookies expire when browser is closed)
 */
var saveToStorage = function (storageType, storageName, value, cookiesExpiration) {
    if (cookiesExpiration === void 0) { cookiesExpiration = -1; }
    // if this storage type is not supported -> do nothing
    if (!isStorageEnabled(storageType))
        return;
    if (storageType === 'cookies') {
        setCookie(storageName, value, cookiesExpiration);
    }
    if (storageType === 'local-storage') {
        setLocalStorageItem(storageName, value);
    }
    if (storageType === 'session-storage') {
        setSessionStorageItem(storageName, value);
    }
};
/**
 * get value from storage
 * @param storageType - 'local-storage', 'session-storage', or 'cookies'
 * @param storageName
 */
var getFromStorage = function (storageType, storageName) {
    // if this storage type is not supported -> do nothing
    if (!isStorageEnabled(storageType))
        return null;
    if (storageType === 'cookies') {
        return getCookie(storageName);
    }
    if (storageType === 'local-storage') {
        return getLocalStorageItem(storageName);
    }
    if (storageType === 'session-storage') {
        return getSessionStorageItem(storageName);
    }
    return null;
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/sort/sort.ts
var sort_spreadArray = (undefined && undefined.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
/**
 * sort by multiple parameters
 * @param girdItems
 * @param paths
 * @return sorted items
 */
var sort = function (girdItems, paths) {
    if (!paths || paths.length <= 0)
        return girdItems;
    var copy = sort_spreadArray([], girdItems, true);
    copy.sort(function (item1, item2) {
        return sortHelper(item1, item2, paths, 0);
    });
    return copy;
};
/**
 * recursive sort helper
 * @param item1
 * @param item2
 * @param paths
 * @param pathIndex
 * @return 0 if equal, <0 if item1 < item2, >0 if item1 > item2
 */
var sortHelper = function (item1, item2, paths, pathIndex) {
    if (pathIndex >= paths.length)
        return 0;
    var result = 0;
    var path = paths[pathIndex];
    if (path.type === 'initial') {
        result = initialSort(item1, item2);
    }
    if (path.type === 'text') {
        result = textSort(item1, item2, path.path, path.direction, path.skip);
    }
    if (path.type === 'number') {
        result = numbersSort(item1, item2, path.path, path.direction);
    }
    if (result === 0) {
        result = sortHelper(item1, item2, paths, pathIndex + 1);
    }
    return result;
};
/**
 * restore back the initial sort
 * @param item1
 * @param item2
 * @return 0 if equal, <0 if item1 < item2, >0 if item1 > item2
 */
var initialSort = function (item1, item2) {
    var order1 = Number(item1.order) || 0;
    var order2 = Number(item2.order) || 0;
    return order1 - order2;
};
/**
 * text sort
 * @param item1
 * @param item2
 * @param path - can be any CSS selector - https://developer.mozilla.org/en-US/docs/Learn/CSS/Introduction_to_CSS/Selectors;
 * empty path means the whole element
 * @param direction - asc or desc
 * @param skip - optional regex that defines characters that should be ignored before the sorting
 * @return 0 if equal, <0 if item1 < item2, >0 if item1 > item2
 */
var textSort = function (item1, item2, path, direction, skip) {
    if (path === void 0) { path = ''; }
    if (direction === void 0) { direction = 'asc'; }
    if (skip === void 0) { skip = '[^a-zA-Z0-9]+'; }
    if (!item1 || !item2) {
        return 0;
    }
    //find elements with the content to sort
    var el1 = path ? item1.querySelector(path) : item1;
    var el2 = path ? item2.querySelector(path) : item2;
    if (!el1 || !el2) {
        return 0;
    }
    var text1 = el1.textContent.trim().toLowerCase();
    var text2 = el2.textContent.trim().toLowerCase();
    if (skip) {
        //regex expression that is used to remove irrelevant characters
        var regexExpr = new RegExp(skip, 'ig');
        text1 = text1.replace(regexExpr, '').trim();
        text2 = text2.replace(regexExpr, '').trim();
    }
    if (text1 === text2) {
        return 0;
    }
    if (!direction) {
        direction = 'asc';
    }
    //compare languages other than English
    if (''.localeCompare) {
        if (direction === 'asc') {
            return text1.localeCompare(text2);
        }
        else {
            return text2.localeCompare(text1);
        }
    }
    else {
        if (direction === 'asc') {
            return text1 > text2 ? 1 : -1;
        }
        else {
            return text1 < text2 ? 1 : -1;
        }
    }
};
/**
 * sort numbers
 * @param item1
 * @param item2
 * @param path - can be any CSS selector - https://developer.mozilla.org/en-US/docs/Learn/CSS/Introduction_to_CSS/Selectors;
 * empty path means the whole element
 * @param direction - asc or desc
 * @return 0 if equal, <0 if item1 < item2, >0 if item1 > item2
 */
var numbersSort = function (item1, item2, path, direction) {
    if (path === void 0) { path = ''; }
    if (direction === void 0) { direction = 'asc'; }
    if (!item1 || !item2) {
        return 0;
    }
    //find elements with the content to sort
    var el1 = path ? item1.querySelector(path) : item1;
    var el2 = path ? item2.querySelector(path) : item2;
    if (!el1 || !el2) {
        return 0;
    }
    var number1str = el1.textContent.trim().toLowerCase();
    var number2str = el2.textContent.trim().toLowerCase();
    //remove other characters
    var number1 = parseFloat(number1str.replace(/[^-0-9.]+/g, ''));
    var number2 = parseFloat(number2str.replace(/[^-0-9.]+/g, ''));
    if (isNaN(number1) || isNaN(number2)) {
        if (isNaN(number1) && isNaN(number2)) {
            return 0;
        }
        else {
            return isNaN(number1) ? 1 : -1;
        }
    }
    if (number1 === number2) {
        return 0;
    }
    if (!direction) {
        direction = 'asc';
    }
    if (direction === 'asc') {
        return number1 - number2;
    }
    else {
        return number2 - number1;
    }
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/filter/filter-interpreter/token.ts
var TokenType;
(function (TokenType) {
    TokenType["OpenParentheses"] = "OpenParentheses";
    TokenType["CloseParentheses"] = "CloseParentheses";
    TokenType["And"] = "And";
    TokenType["Or"] = "Or";
    TokenType["ID"] = "ID";
})(TokenType || (TokenType = {}));
var createToken = function (type, index, value) {
    var res = {
        type: type,
        index: index,
    };
    if (value !== undefined) {
        res.value = value;
    }
    return res;
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/filter/filter-interpreter/lexer.ts


/**
 * Filter Lexer (Scanner).
 * ({...} or {...}) and {...}
 */
var scan = function (text) {
    var result = {
        tokens: [],
        isError: false,
        errors: [],
    };
    /**
     * Handle case when the whole input is empty or whitespace.
     */
    if (isWhiteSpace(text)) {
        return result;
    }
    // replace all newlines with spaces
    text = text.replace(/\n/g, ' ');
    var index = 0;
    // ----------------------------- HELPERS ---------------------------------------------
    /**
     * End of file token should always be added to the end of the tokens list.
     */
    var endOfFile = function () {
        return index >= text.length;
    };
    /**
     * In case of an error, the 'isError' and 'errors' properties should be updated.
     * No token is created.
     */
    var error = function () {
        result.isError = true;
        result.errors.push("DataGrid Custom Filter Error: Bad character input: " + text[index] + ", index: " + index);
    };
    /**
     * ID is any string inside { ... } and it represent jPlist data-id (or name in case Radio button filter).
     * It can be english letter, number, dash or underscore.
     */
    var isId = function (char) {
        return /^[a-zA-Z0-9-_]+$/.test(char);
    };
    var getLastIdToken = function () {
        var lastToken = result.tokens.length > 0 ? result.tokens[result.tokens.length - 1] : null;
        if (lastToken !== null && lastToken.type === TokenType.ID)
            return lastToken;
        lastToken = createToken(TokenType.ID, index, '');
        result.tokens.push(lastToken);
        return lastToken;
    };
    var isAnd = function (startIndex) {
        return text.substring(startIndex, startIndex + 3) === 'and';
    };
    var isOr = function (startIndex) {
        return text.substring(startIndex, startIndex + 2) === 'or';
    };
    // --------------------- LEXER FLOW -------------------------------
    var start = function () {
        result = {
            tokens: [],
            isError: false,
            errors: [],
        };
        if (endOfFile())
            return result;
        var char = text[index];
        switch (char) {
            case ' ': {
                index++;
                start();
                break;
            }
            case '{': {
                openBracket();
                break;
            }
            case '(': {
                openParentheses();
                break;
            }
            default: {
                error();
                break;
            }
        }
    };
    /**
     * '{'
     */
    var openBracket = function () {
        index++;
        // ' .... {' - any string that ends with '{' character is not valid.
        if (endOfFile())
            return error();
        var char = text[index];
        if (char === ' ') {
            index++;
            openBracketWhiteSpace();
        }
        else if (isId(char)) {
            id();
        }
        else
            error();
    };
    var openBracketWhiteSpace = function () {
        // ' .... { ' - any string that ends with '{ ' character and any number of white spaces after it ----> is not valid.
        if (endOfFile())
            return error();
        var char = text[index];
        if (char === ' ') {
            index++;
            openBracketWhiteSpace();
        }
        else if (isId(char)) {
            id();
        }
        else
            error();
    };
    var id = function () {
        var idChar = text[index];
        var lastIdToken = getLastIdToken();
        lastIdToken.value += idChar;
        index++;
        var char = text[index];
        // ' .... aaa ' - any string that ends with a letter/number/dash/underscore character and any number of white spaces after it ----> is not valid.
        if (endOfFile())
            return error();
        if (char === ' ')
            idWhiteSpace();
        else if (isId(char))
            id();
        else if (char === '}')
            closeBracket();
        else
            error();
    };
    var idWhiteSpace = function () {
        // ' .... aaa ' - any string that ends with a letter/number/dash/underscore character and any number of white spaces after it ----> is not valid.
        if (endOfFile())
            return error();
        var char = text[index];
        switch (char) {
            case ' ': {
                index++;
                idWhiteSpace();
                break;
            }
            case '}': {
                closeBracket();
                break;
            }
            default: {
                error();
                break;
            }
        }
    };
    /**
     * '}'
     */
    var closeBracket = function () {
        index++;
        if (endOfFile())
            return result;
        var char = text[index];
        switch (char) {
            case ' ': {
                index++;
                closeBracketWhiteSpace();
                break;
            }
            case ')': {
                closeParentheses();
                break;
            }
            default: {
                error();
                break;
            }
        }
    };
    var closeBracketWhiteSpace = function () {
        if (endOfFile())
            return result;
        var char = text[index];
        if (char === ' ') {
            index++;
            closeBracketWhiteSpace();
        }
        else if (isAnd(index)) {
            and();
        }
        else if (isOr(index)) {
            or();
        }
        else if (char === ')') {
            closeParentheses();
        }
        else
            error();
    };
    var and = function () {
        result.tokens.push(createToken(TokenType.And, index));
        index += 3;
        if (endOfFile())
            return error();
        var char = text[index];
        if (char === ' ')
            andOrWhiteSpace();
        else
            error();
    };
    var or = function () {
        result.tokens.push(createToken(TokenType.Or, index));
        index += 2;
        if (endOfFile())
            return error();
        var char = text[index];
        if (char === ' ')
            andOrWhiteSpace();
        else
            error();
    };
    var andOrWhiteSpace = function () {
        index++;
        if (endOfFile())
            return error();
        var char = text[index];
        switch (char) {
            case ' ': {
                andOrWhiteSpace();
                break;
            }
            case '{': {
                openBracket();
                break;
            }
            case '(': {
                openParentheses();
                break;
            }
            default: {
                error();
                break;
            }
        }
    };
    /**
     * '('
     */
    var openParentheses = function () {
        result.tokens.push(createToken(TokenType.OpenParentheses, index));
        index++;
        // ' .... (' - any string that ends with '(' character is not valid.
        if (endOfFile())
            return error();
        var char = text[index];
        switch (char) {
            case '(': {
                openParentheses();
                break;
            }
            case ' ': {
                index++;
                openParenthesesWhiteSpace();
                break;
            }
            case '{': {
                openBracket();
                break;
            }
        }
    };
    var openParenthesesWhiteSpace = function () {
        if (endOfFile())
            return error();
        var char = text[index];
        switch (char) {
            case ' ': {
                index++;
                openParenthesesWhiteSpace();
                break;
            }
            case '(': {
                openParentheses();
                break;
            }
            case '{': {
                openBracket();
                break;
            }
        }
    };
    /**
     * ')'
     */
    var closeParentheses = function () {
        result.tokens.push(createToken(TokenType.CloseParentheses, index));
        index++;
        if (endOfFile())
            return result;
        var char = text[index];
        if (char === ' ') {
            index++;
            closeParenthesesWhiteSpace();
        }
        else if (char === ')')
            closeParentheses();
        else if (isAnd(index)) {
            and();
        }
        else if (isOr(index)) {
            or();
        }
        else
            error();
    };
    var closeParenthesesWhiteSpace = function () {
        if (endOfFile())
            return result;
        var char = text[index];
        if (char === ' ') {
            index++;
            closeParenthesesWhiteSpace();
        }
        else if (char === ')')
            closeParentheses();
        else if (isAnd(index)) {
            and();
        }
        else if (isOr(index)) {
            or();
        }
        else
            error();
    };
    start();
    return result;
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/filter/filter-interpreter/parse-tree-node.ts
var ParseTreeNodeType;
(function (ParseTreeNodeType) {
    ParseTreeNodeType["BinaryOperator"] = "BinaryOperator";
    ParseTreeNodeType["ID"] = "ID";
})(ParseTreeNodeType || (ParseTreeNodeType = {}));
var createParseTreeNode = function (type, token, left, right) {
    var result = {
        type: type,
        token: token,
        isEnabled: true,
    };
    if (left) {
        result.left = left;
    }
    if (right) {
        result.right = right;
    }
    return result;
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/filter/filter-interpreter/parser.ts


/**
 * Recursive Descent Parser.
 * Parser should create a parse tree.
 * A recursive descent parser is a kind of top-down parser built from a set of mutually recursive procedures (or a non-recursive equivalent) where each such procedure implements one of the non-terminals of the grammar.

 The grammar:
 -----------------------------
 expr -> term | term "or" expr
 term -> factor | factor "and" term
 factor -> "(" expr ")" | ID
 */
var parse = function (lexerResult) {
    var _a;
    var result = {
        root: null,
        isError: false,
        errors: [],
    };
    var tokens = lexerResult.tokens;
    var length = tokens.length;
    // when there is an error in lexer
    if (!lexerResult || lexerResult.isError) {
        (_a = result.errors).push.apply(_a, lexerResult.errors);
        result.isError = true;
        return result;
    }
    // empty string case
    if (!lexerResult.tokens || lexerResult.tokens.length <= 0)
        return result;
    // ----------------------------- HELPERS ---------------------------------------------
    var error = function () {
        var _a;
        result.isError = true;
        var token = tokens[index];
        result.errors.push("ERROR: unexpected token: " + ((_a = token === null || token === void 0 ? void 0 : token.value) !== null && _a !== void 0 ? _a : '') + ", index: " + index);
    };
    // --------------------- PARSER FLOW -------------------------------
    var index = 0;
    /**
     * expr -> term | term "or" expr
     */
    var expr = function () {
        if (index >= length) {
            error();
            return null;
        }
        var termNode = term();
        // expr -> term
        if (index >= length) {
            return termNode;
        }
        // expr -> term "or" expr
        if (tokens[index].type === TokenType.Or) {
            var token = tokens[index];
            index++;
            var rightNode = expr();
            return createParseTreeNode(ParseTreeNodeType.BinaryOperator, token, termNode, rightNode);
        }
        else {
            return termNode;
        }
    };
    /**
     * term -> factor | factor "and" term
     */
    var term = function () {
        if (index >= length) {
            error();
            return null;
        }
        var factorNode = factor();
        // term -> factor
        if (index >= length) {
            return factorNode;
        }
        // term -> factor "and" term
        if (tokens[index].type === TokenType.And) {
            var token = tokens[index];
            index++;
            var rightNode = term();
            return createParseTreeNode(ParseTreeNodeType.BinaryOperator, token, factorNode, rightNode);
        }
        else {
            // term -> factor
            return factorNode;
        }
    };
    /**
     * factor -> "(" expr ")" | ID
     */
    var factor = function () {
        if (index >= length) {
            error();
            return null;
        }
        // factor -> ID
        if (tokens[index].type === TokenType.ID) {
            var tokenNode = createParseTreeNode(ParseTreeNodeType.ID, tokens[index]);
            index++;
            return tokenNode;
        }
        // factor -> "(" expr ")"
        if (tokens[index].type === TokenType.OpenParentheses) {
            index++;
            var exprNode = expr();
            if (tokens[index].type === TokenType.CloseParentheses) {
                index++;
                return exprNode;
            }
            else {
                error();
                return null;
            }
        }
        else {
            error();
            return null;
        }
    };
    var start = function () {
        result = {
            root: null,
            isError: false,
            errors: [],
        };
        result.root = expr();
    };
    start();
    return result;
};
var printTree = function (parserResult) {
    if (!parserResult || !parserResult.root)
        return '';
    var helper = function (root) {
        if (!root)
            return '';
        if (!root.left && !root.right) {
            return "{" + root.token.value + "}";
        }
        var left = root.left ? helper(root.left) : '';
        var right = root.right ? helper(root.right) : '';
        return "(" + left + " " + root.token.type.toLowerCase() + " " + right + ")";
    };
    return helper(parserResult.root);
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/filter/filter.ts
var filter_spreadArray = (undefined && undefined.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};



/**
 * text filter
 * @param $girdItems
 * @param text
 * @param path - any CSS selector or empty value meaning the whole element
 * @param mode - contains (default), startsWith, endsWith, equal
 * @param skip - optional regex that defines what characters should be ignored
 * @return filtered items
 */
var textFilter = function ($girdItems, text, path, mode, skip) {
    if (path === void 0) { path = ''; }
    if (mode === void 0) { mode = 'contains'; }
    if (skip === void 0) { skip = '[^a-zA-Z0-9]+'; }
    var filtered = [];
    if (!$girdItems)
        return [];
    if (text === undefined || text.trim() === '')
        return $girdItems;
    var formattedText = text.replace(new RegExp(skip, 'ig'), '').toLowerCase().trim();
    for (var _i = 0, $girdItems_1 = $girdItems; _i < $girdItems_1.length; _i++) {
        var item = $girdItems_1[_i];
        var $elements = path ? item.querySelectorAll(path) : [item];
        if (!$elements)
            continue;
        var shouldBeAdded = false;
        for (var i = 0; i < $elements.length; i++) {
            var $el = $elements[i];
            var elText = $el.textContent.replace(new RegExp(skip, 'ig'), '').toLowerCase().trim();
            switch (mode) {
                case 'startsWith': {
                    if (elText.startsWith(formattedText)) {
                        shouldBeAdded = true;
                    }
                    break;
                }
                case 'endsWith': {
                    if (elText.endsWith(formattedText)) {
                        shouldBeAdded = true;
                    }
                    break;
                }
                case 'equal': {
                    if (elText === formattedText) {
                        shouldBeAdded = true;
                    }
                    break;
                }
                default: {
                    //contains
                    if (elText.indexOf(formattedText) !== -1) {
                        shouldBeAdded = true;
                    }
                    break;
                }
            }
            if (shouldBeAdded)
                break;
        }
        if (shouldBeAdded) {
            filtered.push(item);
        }
    }
    return filtered;
};
/**
 * path filter
 * only items with the given path are returned
 * @param girdItems
 * @param path - any CSS selector or empty value meaning the whole element
 * @param isInverted - if true, return all items that DON'T contain the specified path
 * @return filtered items
 */
var pathFilter = function (girdItems, path, isInverted) {
    if (path === void 0) { path = ''; }
    if (isInverted === void 0) { isInverted = false; }
    var filtered = [];
    if (!girdItems)
        return [];
    if (path === '' || !path)
        return girdItems;
    for (var _i = 0, girdItems_1 = girdItems; _i < girdItems_1.length; _i++) {
        var item = girdItems_1[_i];
        var el = item.querySelector(path);
        if (el && !isInverted || !el && isInverted) {
            filtered.push(item);
        }
    }
    return filtered;
};
// -------------- AND / OR LOGIC ------------------------------------
var evaluate = function (parserResult, filterActionsMap, $girdItems) {
    if (!parserResult || !parserResult.root)
        return null;
    var helper = function (root) {
        if (!root)
            return null;
        if (!root.left && !root.right) {
            if (!root.token.value)
                return null;
            var currentAction = filterActionsMap.get(root.token.value.toString());
            if (!currentAction)
                return null;
            var hasValue = currentAction.value !== undefined && currentAction.value !== null;
            if (hasValue) {
                root.isEnabled = currentAction.value !== undefined && currentAction.value.trim() !== '';
                if (root.isEnabled) {
                    root.$girdItems = textFilter($girdItems, currentAction.value, currentAction.path, currentAction.mode, currentAction.skip);
                }
            }
            else {
                root.isEnabled = currentAction.path !== '' && !!currentAction.path;
                if (root.isEnabled) {
                    root.$girdItems = pathFilter($girdItems, currentAction.path, currentAction.inverted);
                }
            }
            //console.log(root.token, root.$girdItems.length, `is text filter: ${ hasValue }`)
            return root; // return `{${ root.token.value }}`;
        }
        var leftNode = root.left ? helper(root.left) : null;
        var rightNode = root.right ? helper(root.right) : null;
        var isLeftEnabled = !!leftNode && leftNode.isEnabled;
        var isRightEnabled = !!rightNode && rightNode.isEnabled;
        if (root.token.type === TokenType.Or) {
            if (!isLeftEnabled && !isRightEnabled) {
                root.isEnabled = false;
                root.$girdItems = [];
                return root;
            }
            if (!isLeftEnabled) {
                root.isEnabled = isRightEnabled;
                root.$girdItems = rightNode.$girdItems || [];
                return root;
            }
            if (!isRightEnabled) {
                root.isEnabled = isLeftEnabled;
                root.$girdItems = leftNode.$girdItems || [];
                return root;
            }
            root.isEnabled = true;
            root.$girdItems = Array.from(new Set(filter_spreadArray(filter_spreadArray([], leftNode.$girdItems || [], true), rightNode.$girdItems || [], true)));
            return root;
        }
        // AND ----- find intersection of two arrays ------------------------
        if (!isLeftEnabled && !isRightEnabled) {
            root.isEnabled = false;
            root.$girdItems = $girdItems;
            return root;
        }
        if (!isLeftEnabled) {
            root.isEnabled = isRightEnabled;
            root.$girdItems = rightNode.$girdItems || $girdItems;
            return root;
        }
        if (!isRightEnabled) {
            root.isEnabled = isLeftEnabled;
            root.$girdItems = leftNode.$girdItems || $girdItems;
            return root;
        }
        root.isEnabled = true;
        root.$girdItems = [];
        var intersectionMap = new Map();
        for (var _i = 0, _a = leftNode.$girdItems || $girdItems; _i < _a.length; _i++) {
            var $item = _a[_i];
            intersectionMap.set($item, true);
        }
        for (var _b = 0, _c = rightNode.$girdItems || $girdItems; _b < _c.length; _b++) {
            var $item = _c[_b];
            if (intersectionMap.has($item)) {
                root.$girdItems.push($item);
            }
        }
        return root;
    };
    return helper(parserResult.root);
};
/**
 * Find all filters that are not used in the custom expression.
 */
var checkAllFiltersAreUsed = function (filterActions, lexerResult) {
    var tokens = lexerResult.tokens;
    var notUsedFilters = [];
    var set = new Set();
    for (var _i = 0, tokens_1 = tokens; _i < tokens_1.length; _i++) {
        var token = tokens_1[_i];
        if (token.type !== TokenType.ID)
            continue;
        set.add(token.value.toString().trim());
    }
    for (var _a = 0, filterActions_1 = filterActions; _a < filterActions_1.length; _a++) {
        var action = filterActions_1[_a];
        var dataID = action.dataID.toString().trim();
        if (!set.has(dataID)) {
            notUsedFilters.push(dataID);
        }
    }
    return notUsedFilters;
};
/**
 * Apply a custom AND/Or filter logic.
 */
var customAndOrFilter = function (customFilter, filterActions, $girdItems) {
    if (!$girdItems)
        return [];
    var filterActionsMap = new Map(); // data-id ---> filter action
    for (var _i = 0, filterActions_2 = filterActions; _i < filterActions_2.length; _i++) {
        var filterAction = filterActions_2[_i];
        filterActionsMap.set(filterAction.dataID, filterAction);
    }
    try {
        var lexerResult = scan(customFilter);
        if (lexerResult.isError) {
            console.error(lexerResult.errors);
            console.log('Note that the data-id properties must contain only English letters, numbers, dashes, or underscores.');
            return $girdItems;
        }
        // check if all filter actions are used in the custom filter expression
        var notUsedFilters = checkAllFiltersAreUsed(filterActions, lexerResult);
        if (notUsedFilters.length > 0) {
            console.error("WARNING: the following filters are not used in the AND/OR expression:", notUsedFilters);
            console.log("When a custom filter expression is defined, any filters that are not used in the expression will not work.");
        }
        var parserResult = parse(lexerResult);
        if (parserResult.isError) {
            console.error(lexerResult.errors);
            return $girdItems;
        }
        var result = evaluate(parserResult, filterActionsMap, $girdItems);
        if (!result || !result.$girdItems)
            return [];
        return result.$girdItems || [];
    }
    catch (ex) {
        console.error("DataGrid: Custom filter definition contains an error.");
        return $girdItems;
    }
};

// CONCATENATED MODULE: ./production/src/main/js/domain/actions/pagination/pagination.ts
/**
 * Pagination
 * @param currentPage - current page index
 * @param pageSize - items number per page
 * @param totalSize - total items number
 * @param pagesRange - the number of pages that are visible at once on pagination control
 */
var pagination = function (currentPage, pageSize, totalSize, pagesRange) {
    totalSize = Number(totalSize) || 0;
    pageSize = Number.isInteger(pageSize) ? Number(pageSize) : totalSize;
    if (pageSize === 0) {
        pageSize = totalSize;
    }
    var pagesNumber = pageSize === 0 ? 0 : Math.ceil(totalSize / pageSize);
    //validate current page
    currentPage = Number(currentPage) || 0;
    if (currentPage > pagesNumber - 1) {
        currentPage = 0;
    }
    var firstItemIndex = currentPage * pageSize;
    var lastItemIndex = firstItemIndex + pageSize;
    //validate the last item index
    if (lastItemIndex > totalSize) {
        lastItemIndex = totalSize;
    }
    var prevPage = currentPage <= 0 ? 0 : currentPage - 1;
    var nextPage = pagesNumber === 0 ? 0 : (currentPage >= pagesNumber - 1 ? pagesNumber - 1 : currentPage + 1);
    pagesRange = Number(pagesRange) || 10;
    var halfRange = Math.ceil((pagesRange - 1) / 2);
    var pagesRangeStart = currentPage - halfRange;
    var pagesRangeEnd = Math.min(pagesRangeStart + pagesRange - 1, pagesNumber - 1);
    if (pagesRangeStart <= 0) {
        pagesRangeStart = 0;
        pagesRangeEnd = Math.min(pagesRange - 1, pagesNumber - 1);
    }
    if (pagesRangeEnd >= pagesNumber - 1) {
        pagesRangeStart = Math.max(pagesNumber - pagesRange, 0);
        pagesRangeEnd = pagesNumber - 1;
    }
    return {
        currentPage: currentPage,
        pageSize: pageSize,
        totalSize: totalSize,
        pagesNumber: pagesNumber,
        firstItemIndex: firstItemIndex,
        lastItemIndex: lastItemIndex,
        prevPage: prevPage,
        nextPage: nextPage,
        pagesRange: pagesRange,
        pagesRangeStart: pagesRangeStart,
        pagesRangeEnd: pagesRangeEnd
    };
};

// CONCATENATED MODULE: ./production/src/main/js/domain/log.ts
/**
 * debug logging in case of errors and warnings
 */
var log = function (msg) {
    console.log("DataGrid Library: " + msg);
};

// CONCATENATED MODULE: ./production/src/main/js/ui/grid.ts





var HIDE_ITEM_CLASS_NAME = 'data-grid-hide';
/**
 * get grid placeholder from the page
 */
var getGrid = function ($root) {
    var GRID_DATA_ATTR = 'data-grid';
    var ITEM_DATA_ATTR = 'data-grid-item';
    var $grid = $root.querySelector("[" + GRID_DATA_ATTR + "]");
    if (!$grid) {
        log("The container element with 'data-grid' attribute doesn't exist. Please check documentation https://docs.getdatagrid.com/");
        // it's not possible to continue
        return;
    }
    // collect grid items
    var $items = Array.from($grid.querySelectorAll("[" + ITEM_DATA_ATTR + "]"));
    // set initial sort order; it's used to restore back the initial sort
    for (var i = 0; i < $items.length; i++) {
        $items[i].order = i;
    }
    return {
        $grid: $grid,
        $items: $items
    };
};
/**
 * apply sort on grid items
 */
var applySort = function (sortActions, $visibleItems, $grid) {
    $visibleItems = sort($visibleItems, sortActions);
    for (var i = 0; i < $visibleItems.length; i++) {
        var $item = $visibleItems[i];
        $grid.appendChild($item);
    }
    return $visibleItems;
};
/**
 * apply pagination on grid items
 * show only items in the range [paging.firstItemIndex, paging.lastItemIndex)
 */
var applyPagination = function (state, $visibleItems, totalSize) {
    var itemsAfterPaging = [];
    var paging = pagination(state.currentPage, state.pageSize, totalSize, state.pagesRange);
    for (var j = 0; j < $visibleItems.length; j++) {
        var $item = $visibleItems[j];
        if (j >= paging.firstItemIndex && j < paging.lastItemIndex) {
            itemsAfterPaging.push($item);
        }
    }
    return itemsAfterPaging;
};
/**
 * apply filter on grid items
 */
var applyFilter = function (filterActions, $visibleItems, customFilter) {
    if (customFilter) {
        return customAndOrFilter(customFilter, filterActions, $visibleItems);
    }
    for (var i = 0; i < filterActions.length; i++) {
        var item = filterActions[i];
        var hasValue = item.value !== undefined && item.value !== null;
        if (hasValue) {
            $visibleItems = textFilter($visibleItems, item.value, item.path, item.mode, item.skip);
        }
        else {
            $visibleItems = pathFilter($visibleItems, item.path, item.inverted);
        }
    }
    return $visibleItems;
};
/**
 * show / hide grid items
 */
var showHideGridItems = function ($allItems, $visibleItems) {
    // hide all items
    for (var i = 0; i < $allItems.length; i++) {
        var $item = $allItems[i];
        $item.classList.add(HIDE_ITEM_CLASS_NAME);
    }
    // show only "visible" items
    for (var i = 0; i < $visibleItems.length; i++) {
        var $item = $visibleItems[i];
        $item.classList.remove(HIDE_ITEM_CLASS_NAME);
    }
};
/**
 * show or hide (and update in general) grid items according to the state
 */
var updateGridItems = function (store, rootSlice) {
    var state = store.getState();
    var grid = state.grid;
    var $visibleItems = grid.$items;
    // apply filter on grid items
    var filterActions = getActionsByType(state.actions, 'filter');
    $visibleItems = applyFilter(filterActions, $visibleItems, state.defaults.customFilter);
    var newTotalSize = $visibleItems.length;
    // apply sort on grid items
    var sortActions = getActionsByType(state.actions, 'sort');
    $visibleItems = applySort(sortActions, $visibleItems, grid.$grid);
    // apply pagination on grid items
    $visibleItems = applyPagination(state, $visibleItems, newTotalSize);
    // show / hide grid items
    showHideGridItems(grid.$items, $visibleItems);
    if (state.totalSize !== newTotalSize) {
        var actions = rootSlice.actions;
        var updateTotalSize = actions.updateTotalSize;
        store.dispatch(updateTotalSize({
            totalSize: newTotalSize
        }));
    }
    return $visibleItems;
};

// CONCATENATED MODULE: ./production/src/main/js/ui/controls.ts
/**
 * get all controls from the page
 */
var getControls = function ($root) {
    var controls = [];
    var DATA_ATTR = 'data-grid-control';
    var $controls = $root.querySelectorAll("[" + DATA_ATTR + "]");
    for (var i = 0; i < $controls.length; i++) {
        var $control = $controls[i];
        var type = $control.getAttribute(DATA_ATTR).trim();
        if (!type)
            continue;
        controls.push({
            type: type,
            $control: $control
        });
    }
    return controls;
};
/**
 * Each control should have "data-id" attribute.
 * If the user does not define it, then it should be added dynamically.
 * The "data-id" attribute should be unique with the only exception:
 * when the same control appears in the top and the bottom panels at the same time.
 */
var handleDataID = function (controls) {
    var index = 0;
    for (var i = 0; i < controls.length; i++) {
        var control = controls[i];
        var dataID = control.$control.getAttribute('data-id');
        if (dataID === null || dataID === undefined) {
            control.$control.setAttribute('data-id', index.toString());
            index++;
        }
    }
};
/**
 * render controls
 * @param {object} store
 * @param {object} rootSlice
 */
var renderControls = function (store, rootSlice) {
    var state = store.getState();
    for (var i = 0; i < state.controls.length; i++) {
        var control = state.controls[i];
        var controlFunc = window.datagridControls && window.datagridControls[control.type];
        if (!controlFunc)
            continue;
        control.$control.datagrid = controlFunc(control, store, rootSlice);
    }
};

// CONCATENATED MODULE: ./production/src/main/js/data/deep-link.ts

/**
 * generate deep link from state
 */
var stateToDeepLink = function (state) {
    if (!state || !window.URLSearchParams)
        return '';
    var params = {
        currentPage: state.currentPage,
        pageSize: state.pageSize,
        pagesRange: state.pagesRange
    };
    if (state.actions) {
        for (var _i = 0, _a = Object.values(state.actions); _i < _a.length; _i++) {
            var action = _a[_i];
            var dataID = action.dataID;
            if (dataID === undefined || dataID === null)
                continue;
            for (var _b = 0, _c = Object.entries(action); _b < _c.length; _b++) {
                var _d = _c[_b], key = _d[0], value = _d[1];
                if (key !== 'dataID') {
                    params[key + "-" + dataID] = value;
                }
            }
        }
    }
    return new URLSearchParams(params).toString();
};
/**
 * if value is a number or a boolean -> parse it
 */
var parseValue = function (value) {
    if (isNumeric(value)) {
        value = Number(value);
    }
    if (value === 'true') {
        value = true;
    }
    if (value === 'false') {
        value = false;
    }
    return value;
};
/**
 * convert deep link to defaults
 */
var deepLinkToDefaults = function (deepLink) {
    var pairs = [];
    var actions = {};
    var parts = deepLink.replace('?', '').split('&');
    for (var i = 0; i < parts.length; i++) {
        var part = parts[i].split('=');
        if (part.length !== 2)
            continue;
        var key = part[0];
        var value = part[1];
        if (key === 'currentPage' || key === 'pageSize' || key === 'pagesRange') {
            pairs.push({
                key: key,
                value: parseValue(value)
            });
        }
        else {
            // actions -------------------
            var index = key.lastIndexOf('-');
            if (index === -1)
                continue;
            var dataID = key.substring(index + 1);
            var propertyName = key.substring(0, index);
            if (actions[dataID] === undefined) {
                actions[dataID] = {
                    dataID: dataID
                };
            }
            actions[dataID][propertyName] = parseValue(value);
        }
    }
    pairs.push({
        key: 'actions',
        value: actions
    });
    return pairs;
};

// CONCATENATED MODULE: ./production/src/main/js/data/ajax.ts
var __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var ajax_generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};

/**
 * get data from the give URL
 */
var bringData = function (state) { return __awaiter(void 0, void 0, void 0, function () {
    var deepLink, url, response;
    return ajax_generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!('URL' in window))
                    return [2 /*return*/];
                deepLink = stateToDeepLink(state);
                url = new URL(state.defaults.dataSourceURL);
                url.search = deepLink;
                return [4 /*yield*/, fetch(url.href)];
            case 1:
                response = _a.sent();
                return [2 /*return*/, response.json()];
        }
    });
}); };

// CONCATENATED MODULE: ./production/src/main/js/main.ts
var __assign = (undefined && undefined.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var main_awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var main_generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};







/**
 * get defaults
 */
var getDefaults = function (settings) {
    var defaults = {
        // pagination
        currentPage: 0,
        pageSize: Infinity,
        pagesRange: 10,
        // all other actions like sort, filters etc.
        actions: {},
        initialActions: {},
        // deep link and storage
        deepLink: false,
        storage: '',
        storageName: 'datagrid',
        // data source
        dataSource: 'html',
        dataSourceURL: '',
        // optional custom filter
        customFilter: '',
        /**
         * convert JSON to HTML
         * used in AJAX data source
         */
        dataToHTML: function (data) {
            return data.map(function (item) {
                return "\n                    <div class=\"col-md-4\" data-grid-item>\n                        <div class=\"card mb-4 shadow-sm\">\n                            <div class=\"card-body\">\n                                <h5 class=\"card-title\">" + item.title + "</h5>\n                                <p class=\"card-text\">" + item.body + "</p>\n                            </div>\n                        </div>\n                    </div>\n                ";
            }).join('');
        },
        // callbacks
        onChange: function () { }
    };
    // merge defaults with settings
    defaults = deepMerge(defaults, settings);
    if (defaults.storage !== '') {
        var storageString = getFromStorage(defaults.storage, defaults.storageName);
        var storageData = storageString ? deepLinkToDefaults(storageString) : [];
        for (var i = 0; i < storageData.length; i++) {
            var pair = storageData[i];
            defaults[pair.key] = pair.value;
        }
    }
    if (defaults.deepLink) {
        var deepLinkData = window.location.search ? deepLinkToDefaults(window.location.search) : [];
        // override storage defaults with deep link data if needed
        for (var i = 0; i < deepLinkData.length; i++) {
            var pair = deepLinkData[i];
            defaults[pair.key] = pair.value;
        }
    }
    return defaults;
};
/**
 * build initial state
 */
var buildInitialState = function (settings, $root) {
    var defaults = getDefaults(settings);
    var initialState = {
        defaults: defaults,
        $root: $root
    };
    // get grid placeholder from the page
    initialState.grid = getGrid($root);
    if (!initialState.grid) {
        return null;
    }
    // the dictionary of all actions like sort, filter etc.
    // each action is defined by control's "data-id" attribute
    initialState.actions = defaults.actions || {};
    initialState.initialActions = JSON.parse(JSON.stringify(defaults.actions || {}));
    // pagination
    initialState.currentPage = defaults.currentPage;
    initialState.pageSize = defaults.pageSize;
    initialState.pagesRange = defaults.pagesRange;
    initialState.totalSize = undefined; // total size after filters is exists any; in case of ajax data source, total size is undefined till ajax brings the data
    // totalSize in case of html data source; in case of ajax -> totalSize will be initialized after the first ajax request because it needs "totalSize"
    if (defaults.dataSource === 'html') {
        initialState.totalSize = initialState.grid.$items.length;
    }
    // get all controls from the page
    initialState.controls = getControls($root);
    // Each control should have "data-id" attribute.
    // If the user does not define it, then it should be added dynamically.
    // The "data-id" attribute should be unique with the only exception:
    // when the same control appears in the top and the bottom panels at the same time.
    handleDataID(initialState.controls);
    return initialState;
};
/**
 * main update
 */
var update = function (state, store, rootSlice) { return main_awaiter(void 0, void 0, void 0, function () {
    var dataSource, $visibleItems, data, totalSize, actions, updateTotalSize, deepLink, url, deepLink;
    return main_generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                dataSource = state.defaults.dataSource;
                // update grid helper classes
                state.grid.$grid.classList.add("data-grid-" + dataSource);
                if (dataSource === 'html') {
                    $visibleItems = updateGridItems(store, rootSlice);
                    state.grid.$grid.classList.toggle("data-grid-empty", $visibleItems.length <= 0);
                }
                if (!(dataSource === 'ajax')) return [3 /*break*/, 2];
                return [4 /*yield*/, bringData(state)];
            case 1:
                data = _a.sent();
                totalSize = data.length;
                if (state.totalSize === undefined || state.totalSize !== totalSize) {
                    actions = rootSlice.actions;
                    updateTotalSize = actions.updateTotalSize;
                    store.dispatch(updateTotalSize({
                        totalSize: totalSize
                    }));
                }
                if (typeof state.defaults.dataToHTML === 'function') {
                    state.grid.$grid.innerHTML = state.defaults.dataToHTML(data);
                }
                else {
                    console.error('DataGrid Error: dataToHTML function is not provided.');
                }
                _a.label = 2;
            case 2:
                if (dataSource !== 'ajax' || dataSource === 'ajax' && state.totalSize !== undefined) {
                    if (state.defaults.deepLink) {
                        deepLink = stateToDeepLink(state);
                        if (window.location.search !== deepLink && 'URL' in window) {
                            url = new URL(window.location.href);
                            url.search = deepLink;
                            window.history.replaceState('', '', url.href);
                        }
                    }
                    if (state.defaults.storage !== '') {
                        deepLink = stateToDeepLink(state);
                        saveToStorage(state.defaults.storage, state.defaults.storageName, deepLink);
                    }
                }
                if (typeof state.defaults.onChange === 'function') {
                    state.defaults.onChange(state);
                }
                return [2 /*return*/];
        }
    });
}); };
/**
 * entry point
 */
var main = function (settings, $root) { return main_awaiter(void 0, void 0, void 0, function () {
    var initialState, rootSlice, store;
    return main_generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                initialState = buildInitialState(settings, $root);
                if (!initialState)
                    return [2 /*return*/];
                rootSlice = createSlice({
                    name: 'root',
                    initialState: initialState,
                    reducers: {
                        updateTotalSize: function (state, action) {
                            return __assign(__assign({}, state), { totalSize: action.payload.totalSize });
                        },
                        updatePagination: function (state, action) {
                            var currentPage = action.payload.currentPage;
                            var pageSize = action.payload.pageSize;
                            var pagesRange = action.payload.pagesRange;
                            return __assign(__assign({}, state), { currentPage: currentPage, pageSize: pageSize, pagesRange: pagesRange });
                        },
                        updateActions: function (state, action) {
                            var dataID = action.payload.dataID;
                            var actions = __assign({}, state.actions);
                            actions[dataID] = action.payload;
                            return __assign(__assign({}, state), { actions: actions, currentPage: 0 });
                        }
                    }
                });
                store = configureStore({
                    reducer: rootSlice.reducer
                });
                // render controls according to the state
                renderControls(store, rootSlice);
                // first update
                return [4 /*yield*/, update(initialState, store, rootSlice)];
            case 1:
                // first update
                _a.sent();
                store.subscribe(function () { return main_awaiter(void 0, void 0, void 0, function () {
                    var state;
                    return main_generator(this, function (_a) {
                        switch (_a.label) {
                            case 0:
                                state = store.getState();
                                return [4 /*yield*/, update(state, store, rootSlice)];
                            case 1:
                                _a.sent();
                                return [2 /*return*/];
                        }
                    });
                }); });
                return [2 /*return*/];
        }
    });
}); };

// CONCATENATED MODULE: ./production/src/main/js/domain/controls/pagination/pagination-control.ts


/*
 Pagination Control

 HTML:
 -----
 <nav
    aria-label="pagination"
    data-grid-control="pagination"
    data-prev="&laquo;"
    data-next="&raquo;"
    data-first="First"
    data-last="Last"
    data-hide-first-last="true"
    data-scroll-top>
 </nav>
 
 Settings
 --------
{
    currentPage: 0,
    pageSize: 10,
    pagesRange: 10,
    renderPaginationControl: (state, $control) => {
        return '...html...';
    }
}
*/
window.datagridControls = window.datagridControls || {};
/**
 * render HTML
 */
var render = function (state, $control) {
    var paging = pagination(state.currentPage, state.pageSize, state.totalSize, state.pagesRange);
    $control.classList.toggle('dg-pagination-empty', paging.pagesNumber <= 0);
    if (state.totalSize <= 0)
        return '';
    var isFirstPage = paging.currentPage === 0;
    var isLastPage = paging.currentPage === paging.pagesNumber - 1;
    var buttons = [];
    for (var i = paging.pagesRangeStart; i <= paging.pagesRangeEnd; i++) {
        buttons.push({
            index: i,
            title: i + 1
        });
    }
    var firstText = $control.getAttribute('data-first') || 'First';
    var lastText = $control.getAttribute('data-last') || 'Last';
    var prevText = $control.getAttribute('data-prev') || '&laquo;';
    var nextText = $control.getAttribute('data-next') || '&raquo;';
    var hideFirstLast = $control.getAttribute('data-hide-first-last') === 'true';
    var ulClass = $control.getAttribute('data-ul-class') || 'pagination';
    var liClass = $control.getAttribute('data-li-class') || 'page-item';
    var linkClass = $control.getAttribute('data-link-class') || 'page-link';
    var disabledClass = $control.getAttribute('data-disabled-class') || 'disabled';
    var activeClass = $control.getAttribute('data-active-class') || 'active';
    var prevClass = $control.getAttribute('data-prev-class') || 'page-prev';
    var nextClass = $control.getAttribute('data-next-class') || 'page-next';
    var firstClass = $control.getAttribute('data-first-class') || 'page-first';
    var lastClass = $control.getAttribute('data-last-class') || 'page-last';
    return "\n        <ul class=\"" + ulClass + "\" data-grid-control=\"pagination\">\n        \n        " + (!hideFirstLast ? "<li class=\"" + liClass + " " + (isFirstPage ? disabledClass : '') + " " + firstClass + "\">\n                " + (isFirstPage ? "<span class=\"" + linkClass + "\">" + firstText + "</span>" : "<a class=\"" + linkClass + "\" href=\"#\" data-grid-page=\"" + 0 + "\">" + firstText + "</a>") + "\n            </li>" : '') + "\n            \n            <li class=\"" + liClass + " " + (isFirstPage ? disabledClass : '') + " " + prevClass + "\">\n                " + (isFirstPage ? "<span class=\"" + linkClass + "\">" + prevText + "</span>" : "<a class=\"" + linkClass + "\" href=\"#\" data-grid-page=\"" + (paging.currentPage - 1) + "\">" + prevText + "</a>") + "\n            </li>\n            \n            " + buttons.map(function (button) {
        var isCurrentPage = paging.currentPage === button.index;
        return "\n                        <li class=\"" + liClass + " " + (isCurrentPage ? activeClass : '') + "\" " + (isCurrentPage ? 'aria-current="page"' : '') + ">\n                            " + (!isCurrentPage ? "<a class=\"" + linkClass + "\" href=\"#\" data-grid-page=\"" + button.index + "\">" + button.title + "</a>" : '') + "\n                            " + (isCurrentPage ? "<span class=\"" + linkClass + "\" data-grid-page=\"" + button.index + "\">" + button.title + "<span class=\"sr-only\">(current)</span></span>" : '') + "\n                        </li>\n                        ";
    }).join('') + "\n            \n            <li class=\"" + liClass + " " + (isLastPage ? disabledClass : '') + " " + nextClass + "\">\n                " + (isLastPage ? "<span class=\"" + linkClass + "\">" + nextText + "</span>" : "<a class=\"" + linkClass + "\" href=\"#\" data-grid-page=\"" + (paging.currentPage + 1) + "\">" + nextText + "</a>") + "\n            </li>\n            \n            " + (!hideFirstLast ? "<li class=\"" + liClass + " " + (isLastPage ? disabledClass : '') + " " + lastClass + "\">\n                " + (isLastPage ? "<span class=\"" + linkClass + "\">" + lastText + "</span>" : "<a class=\"" + linkClass + "\" href=\"#\" data-grid-page=\"" + (paging.pagesNumber - 1) + "\">" + lastText + "</a>") + "\n            </li>" : '') + "\n        </ul>\n    ";
};
/**
 * init pagination control
 * generate pagination control HTML according to the state
 */
var init = function (control, store, rootSlice) {
    var state = store.getState();
    var actions = rootSlice.actions;
    var updatePagination = actions.updatePagination;
    /*
    currentPage: 0
    firstItemIndex: 0
    lastItemIndex: 10
    nextPage: 1
    pageSize: 10
    pagesNumber: 10
    pagesRange: 10
    pagesRangeEnd: 9
    pagesRangeStart: 0
    prevPage: 0
    totalSize: 100
    */
    control.$control.innerHTML = typeof state.defaults.renderPaginationControl === 'function' ? state.defaults.renderPaginationControl(state, control.$control) : render(state, control.$control);
    var $buttons = control.$control.querySelectorAll('[data-grid-page]');
    var shouldScrollTop = control.$control.hasAttribute('data-scroll-top');
    for (var i = 0; i < $buttons.length; i++) {
        var $button = $buttons[i];
        $button.addEventListener('click', function (evt) {
            evt.preventDefault();
            var $target = evt.currentTarget;
            var currentPage = Number($target.getAttribute('data-grid-page')) || 0;
            store.dispatch(updatePagination({
                currentPage: currentPage,
                pageSize: state.pageSize,
                pagesRange: state.pagesRange
            }));
            if (shouldScrollTop) {
                scrollTop();
            }
        });
    }
};
/**
 * pagination control
 * generate pagination control HTML according to the state
 */
window.datagridControls.pagination = function (control, store, rootSlice) {
    var state = store.getState();
    var initialCurrentPage = state.currentPage;
    // re-initiate on each store update
    store.subscribe(function () {
        init(control, store, rootSlice);
    });
    // initiate first time
    init(control, store, rootSlice);
    return {
        reset: function () {
            var state = store.getState();
            var actions = rootSlice.actions;
            var updatePagination = actions.updatePagination;
            store.dispatch(updatePagination({
                currentPage: initialCurrentPage,
                pageSize: state.pageSize,
                pagesRange: state.pagesRange
            }));
        },
    };
};

// CONCATENATED MODULE: ./production/src/main/js/domain/controls/pagination/page-size-control.ts

window.datagridControls = window.datagridControls || {};
/**
 * on change
 */
var onChange = function (evt) {
    var $control = evt.currentTarget;
    var store = $control.store;
    var state = store.getState();
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updatePagination = actions.updatePagination;
    var pageSize = Number($control.value);
    if ($control.shouldScrollTop) {
        scrollTop();
    }
    store.dispatch(updatePagination({
        currentPage: 0,
        pageSize: pageSize,
        pagesRange: state.pagesRange
    }));
};
/**
 * page size control
 * defined number of pagination items per page
 */
window.datagridControls['page-size'] = function (control, store, rootSlice) {
    // on change outside
    // for example, if there are multiple such control on the page, and one of them has changed
    store.subscribe(function () {
        var state = store.getState();
        $control.value = state.pageSize;
    });
    var state = store.getState();
    var $control = control.$control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.shouldScrollTop = control.$control.hasAttribute('data-scroll-top');
    // select initial value
    $control.value = state.pageSize;
    var initialPageSize = state.pageSize;
    $control.removeEventListener('change', onChange);
    $control.addEventListener('change', onChange);
    return {
        reset: function () {
            var state = store.getState();
            var actions = rootSlice.actions;
            var updatePagination = actions.updatePagination;
            store.dispatch(updatePagination({
                currentPage: state.currentPage,
                pageSize: initialPageSize,
                pagesRange: state.pagesRange
            }));
        },
    };
};

// CONCATENATED MODULE: ./production/src/main/js/domain/controls/labels/label-control.ts
/*
 Label Control

 HTML:
 -----
 <div data-grid-control="label" data-type="pagination-pages|pagination-items|no-results"></div>

 Settings
 --------
 {
    renderLabelControl: (state, $control) => {
        return '...html...';
    }
 }
*/

window.datagridControls = window.datagridControls || {};
/**
 * render HTML
 */
var label_control_render = function (state, $control) {
    var paging = pagination(state.currentPage, state.pageSize, state.totalSize, state.pagesRange);
    var type = $control.getAttribute('data-type');
    var text = $control.getAttribute('data-text');
    if (type === 'pagination-pages') {
        $control.classList.toggle('dg-label-empty', paging.pagesNumber <= 0);
        return paging.pagesNumber > 0 ? "Page " + (paging.currentPage + 1) + " of " + paging.pagesNumber : '';
    }
    if (type === 'pagination-items') {
        $control.classList.toggle('dg-label-empty', paging.pagesNumber <= 0);
        return paging.totalSize > 0 ? paging.firstItemIndex + 1 + " - " + paging.lastItemIndex + " of " + paging.totalSize : '';
    }
    if (type === 'no-results') {
        $control.classList.toggle('dg-label-empty', state.totalSize !== 0);
        return state.totalSize === 0 ? (text ? text : 'No results found.') : '';
    }
    return '';
};
/**
 * label control init
 * defined number of pagination items per page
 */
var label_control_init = function (control, store, rootSlice) {
    var state = store.getState();
    /*
    currentPage: 0
    firstItemIndex: 0
    lastItemIndex: 10
    nextPage: 1
    pageSize: 10
    pagesNumber: 10
    pagesRange: 10
    pagesRangeEnd: 9
    pagesRangeStart: 0
    prevPage: 0
    totalSize: 100
    */
    control.$control.innerHTML = typeof state.defaults.renderLabelControl === 'function' ? state.defaults.renderLabelControl(state, control.$control) : label_control_render(state, control.$control);
};
/**
 * page size control
 * defined number of pagination items per page
 */
window.datagridControls['label'] = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        label_control_init(control, store, rootSlice);
    });
    // initiate first time
    label_control_init(control, store, rootSlice);
};

// CONCATENATED MODULE: ./production/src/main/js/domain/controls/sort/sort-dropdown.ts
window.datagridControls = window.datagridControls || {};
/**
 * update datagrid general state
 */
var sort_dropdown_update = function ($control) {
    var $option = $control.options[$control.selectedIndex];
    var path = $option.getAttribute('data-path');
    var direction = $option.getAttribute('data-direction') || 'asc';
    var type = $option.getAttribute('data-type') || 'text';
    var skip = $option.getAttribute('data-skip');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.dataID,
        path: path,
        direction: direction,
        type: type
    };
    if (skip) {
        action.skip = skip;
    }
    store.dispatch(updateActions(action));
};
/**
 * on change
 */
var sort_dropdown_onChange = function (evt) {
    sort_dropdown_update(evt.currentTarget);
};
/**
 * restore control state
 */
var restore = function (control, store, action) {
    if (!action)
        return;
    var $control = control.$control;
    var path = action.path;
    var direction = action.direction || 'asc';
    var type = action.type || 'text';
    var optionIndex = Array.from($control.options).findIndex(function ($option) {
        var oPath = $option.getAttribute('data-path');
        var oDirection = $option.getAttribute('data-direction') || 'asc';
        var oType = $option.getAttribute('data-type') || 'text';
        return oPath === path && oDirection === direction && oType === type;
    });
    if (optionIndex !== -1) {
        $control.selectedIndex = optionIndex;
    }
};
/**
 * sort dropdown control
 */
window.datagridControls.sort = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        var state = store.getState();
        restore(control, store, state.actions[$control.dataID]);
    });
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    var state = store.getState();
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore sort control state first time
        restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        sort_dropdown_update($control);
    }
    var initialIndex = $control.selectedIndex;
    $control.removeEventListener('change', sort_dropdown_onChange);
    $control.addEventListener('change', sort_dropdown_onChange);
    return {
        reset: function () {
            $control.selectedIndex = initialIndex;
            sort_dropdown_update($control);
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/sort/sort-button.ts
window.datagridControls = window.datagridControls || {};
// class names
var ASC = 'dg-asc';
var DESC = 'dg-desc';
var CHECKED = 'dg-sort-selected';
/**
 * update control classes
 */
var updateClasses = function ($control, path) {
    $control.classList.remove(ASC);
    $control.classList.remove(DESC);
    $control.classList.remove(CHECKED);
    if ($control.path === path) {
        $control.classList.add(CHECKED);
    }
    if ($control.direction === 'asc') {
        $control.classList.add(ASC);
    }
    if ($control.direction === 'desc') {
        $control.classList.add(DESC);
    }
};
/**
 * update datagrid general state
 */
var sort_button_update = function ($control) {
    var skip = $control.getAttribute('data-skip');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.dataID,
        path: $control.path,
        direction: $control.direction,
        type: $control.type
    };
    if (skip) {
        action.skip = skip;
    }
    store.dispatch(updateActions(action));
    // update control classes -------------------------------------
    updateClasses($control, $control.path);
};
/**
 * on click event
 */
var onClick = function (evt) {
    evt.preventDefault();
    var $control = evt.currentTarget;
    if ($control.toggleDirection) {
        $control.direction = $control.direction === 'asc' ? 'desc' : 'asc';
    }
    sort_button_update($control);
};
/**
 * restore control state
 */
var sort_button_restore = function (control, store, action) {
    if (!action)
        return;
    var $control = control.$control;
    $control.direction = action.direction || 'asc';
    updateClasses($control, action.path);
};
/**
 * sort button control
 */
window.datagridControls['sort-button'] = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        var state = store.getState();
        sort_button_restore(control, store, state.actions[$control.dataID]);
    });
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    $control.direction = $control.getAttribute('data-direction') || 'asc';
    $control.toggleDirection = $control.hasAttribute('data-toggle-direction');
    $control.path = $control.getAttribute('data-path');
    $control.type = $control.getAttribute('data-type') || 'text';
    var state = store.getState();
    var initialDirection = $control.direction;
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore sort control state first time
        sort_button_restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        sort_button_update($control);
    }
    $control.removeEventListener('click', onClick);
    $control.addEventListener('click', onClick);
    // returns an API ..........
    return {
        reset: function () {
            if ($control.dataID && state.initialActions && state.actions[$control.dataID] !== undefined) {
                sort_button_restore(control, store, state.actions[$control.dataID]);
            }
            else {
                $control.direction = initialDirection;
                sort_button_update($control);
            }
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/filter/text-filter.ts
window.datagridControls = window.datagridControls || {};
/**
 * update datagrid general state
 */
var text_filter_update = function ($control) {
    var value = $control.value;
    var path = $control.getAttribute('data-path');
    var mode = $control.getAttribute('data-mode') || 'contains';
    var skip = $control.getAttribute('data-skip');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.dataID,
        value: value,
        path: path,
        mode: mode
    };
    if (skip !== undefined && skip !== null) {
        action.skip = skip;
    }
    store.dispatch(updateActions(action));
};
/**
 * on input event
 */
var onInput = function (evt) {
    text_filter_update(evt.currentTarget);
};
/**
 * restore control state
 */
var text_filter_restore = function (control, store, action) {
    if (!action)
        return;
    var $control = control.$control;
    $control.value = action.value;
};
/**
 * text filter control
 */
window.datagridControls['text-filter'] = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        var state = store.getState();
        text_filter_restore(control, store, state.actions[$control.dataID]);
    });
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    var state = store.getState();
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore control state first time
        text_filter_restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        text_filter_update($control);
    }
    $control.removeEventListener('input', onInput);
    $control.addEventListener('input', onInput);
    // returns an API ..........
    return {
        reset: function () {
            var _a;
            if ($control.dataID && state.initialActions && state.actions[$control.dataID] !== undefined) {
                text_filter_restore(control, store, state.actions[$control.dataID]);
            }
            else {
                $control.value = (_a = $control.getAttribute('value')) !== null && _a !== void 0 ? _a : '';
                text_filter_update($control);
            }
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/filter/select-filter.ts
window.datagridControls = window.datagridControls || {};
/**
 * update datagrid general state
 */
var select_filter_update = function ($control) {
    var $option = $control.options[$control.selectedIndex];
    var path = $option.getAttribute('data-path');
    var inverted = $option.getAttribute('data-inverted') === 'true';
    var value = $option.getAttribute('data-value');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.dataID,
        path: path
    };
    if (inverted) {
        action.inverted = inverted;
    }
    if (value !== undefined && value !== null) {
        action.value = value;
        action.mode = $option.getAttribute('data-mode') || 'equal';
        var skip = $option.getAttribute('data-skip');
        if (skip !== undefined && skip !== null) {
            action.skip = skip;
        }
    }
    store.dispatch(updateActions(action));
};
/**
 * on change event
 */
var select_filter_onChange = function (evt) {
    select_filter_update(evt.currentTarget);
};
/**
 * restore control state
 */
var select_filter_restore = function (control, store, action) {
    if (!action)
        return;
    var $control = control.$control;
    var path = action.path;
    var inverted = action.inverted;
    var optionIndex = Array.from($control.options).findIndex(function ($option) {
        var oPath = $option.getAttribute('data-path');
        var oInverted = $option.getAttribute('data-inverted') === 'true';
        return oInverted ? (oPath === path && oInverted === inverted) : oPath === path;
    });
    if (optionIndex !== -1) {
        $control.selectedIndex = optionIndex;
    }
};
/**
 * select filter control
 */
window.datagridControls['select-filter'] = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        var state = store.getState();
        select_filter_restore(control, store, state.actions[$control.dataID]);
    });
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    var state = store.getState();
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore control state first time
        select_filter_restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        select_filter_update($control);
    }
    $control.removeEventListener('change', select_filter_onChange);
    $control.addEventListener('change', select_filter_onChange);
    return {
        reset: function () {
            var _a;
            if ($control.dataID && state.initialActions && state.initialActions[$control.dataID] !== undefined) {
                select_filter_restore(control, store, state.initialActions[$control.dataID]);
            }
            else {
                var index = Array.from((_a = $control.options) !== null && _a !== void 0 ? _a : []).findIndex(function ($option) { return $option.hasAttribute('selected'); });
                $control.selectedIndex = index == -1 ? 0 : index;
                select_filter_update($control);
            }
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/filter/checkbox-filter.ts
window.datagridControls = window.datagridControls || {};
/**
 * update datagrid general state
 */
var checkbox_filter_update = function ($control) {
    var path = $control.getAttribute('data-path');
    var inverted = $control.getAttribute('data-inverted') === 'true';
    var value = $control.getAttribute('data-value');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.dataID,
        path: $control.checked ? path : ''
    };
    if (inverted) {
        action.inverted = inverted;
    }
    if ($control.checked && value !== undefined && value !== null) {
        action.value = value;
        action.mode = $control.getAttribute('data-mode') || 'equal';
        var skip = $control.getAttribute('data-skip');
        if (skip !== undefined && skip !== null) {
            action.skip = skip;
        }
    }
    store.dispatch(updateActions(action));
};
/**
 * on change event
 */
var checkbox_filter_onChange = function (evt) {
    checkbox_filter_update(evt.currentTarget);
};
/**
 * restore control state
 */
var checkbox_filter_restore = function (control, store, action) {
    if (!action)
        return;
    var $control = control.$control;
    var path = action.path;
    // const inverted = action.inverted;
    $control.checked = path !== undefined && path !== null && path.trim() !== '';
};
/**
 * checkbox filter control
 */
window.datagridControls['checkbox-filter'] = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        var state = store.getState();
        checkbox_filter_restore(control, store, state.actions[$control.dataID]);
    });
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    var state = store.getState();
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore control state first time
        checkbox_filter_restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        checkbox_filter_update($control);
    }
    $control.removeEventListener('change', checkbox_filter_onChange);
    $control.addEventListener('change', checkbox_filter_onChange);
    return {
        reset: function () {
            if ($control.dataID && state.initialActions && state.initialActions[$control.dataID] !== undefined) {
                checkbox_filter_restore(control, store, state.initialActions[$control.dataID]);
            }
            else {
                $control.checked = $control.getAttribute('data-checked') === 'true';
                checkbox_filter_update($control);
            }
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/filter/radio-filter.ts
window.datagridControls = window.datagridControls || {};
/**
 * update datagrid general state
 */
var radio_filter_update = function ($control) {
    var path = $control.getAttribute('data-path');
    var inverted = $control.getAttribute('data-inverted') === 'true';
    var value = $control.getAttribute('data-value');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.name,
        path: $control.checked ? path : ''
    };
    if (inverted) {
        action.inverted = inverted;
    }
    if (value !== undefined && value !== null) {
        action.value = value;
        action.mode = $control.getAttribute('data-mode') || 'equal';
        var skip = $control.getAttribute('data-skip');
        if (skip !== undefined && skip !== null) {
            action.skip = skip;
        }
    }
    if ($control.checked) {
        store.dispatch(updateActions(action));
    }
};
/**
 * on change event
 */
var radio_filter_onChange = function (evt) {
    radio_filter_update(evt.currentTarget);
};
/**
 * restore control state
 */
var radio_filter_restore = function (control, store, action) {
    /*
    if(!action) return;

    const $control = control.$control as RadioButtonFilterControl;

    if($control.hasAttribute('checked')){
        $control.checked = (control.$control as HTMLInputElement).checked;
    }
    else{
        const path = action.path;
        const value = $control.getAttribute('data-value');
        const hasValue = value !== undefined && value !== null;

        let checked = path !== undefined && path !== null;

        if(checked && !hasValue){
            checked = path?.trim() !== '';
        }

        if(checked && hasValue && action.value !== undefined && action.value !== null){
            checked = action.value === value;
        }

        $control.checked = checked;
    }*/
};
/**
 * radio button filter control
 */
window.datagridControls['radio-filter'] = function (control, store, rootSlice) {
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.name;
    var state = store.getState();
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore control state for the first time
        radio_filter_restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        radio_filter_update($control);
    }
    var isInitialChecked = $control.checked;
    $control.removeEventListener('change', radio_filter_onChange);
    $control.addEventListener('change', radio_filter_onChange);
    return {
        reset: function () {
            if ($control.dataID && state.initialActions && state.initialActions[$control.dataID] !== undefined) {
                radio_filter_restore(control, store, state.initialActions[$control.dataID]);
            }
            else {
                if (isInitialChecked) {
                    $control.checked = isInitialChecked;
                    radio_filter_update($control);
                }
            }
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/filter/button-filter.ts
window.datagridControls = window.datagridControls || {};
/**
 * update datagrid general state
 */
var button_filter_update = function ($control) {
    var path = $control.getAttribute('data-path');
    var inverted = $control.getAttribute('data-inverted') === 'true';
    var value = $control.getAttribute('data-value');
    // update the store -------------------------------------
    var control = $control.control;
    var store = $control.store;
    var rootSlice = $control.rootSlice;
    var actions = rootSlice.actions;
    var updateActions = actions.updateActions;
    var action = {
        control: control.type,
        dataID: $control.dataID,
        path: $control.checked ? path : ''
    };
    if (inverted) {
        action.inverted = inverted;
    }
    if ($control.checked && value !== undefined && value !== null) {
        action.value = value;
        action.mode = $control.getAttribute('data-mode') || 'equal';
        var skip = $control.getAttribute('data-skip');
        if (skip !== undefined && skip !== null) {
            action.skip = skip;
        }
    }
    store.dispatch(updateActions(action));
};
/**
 * on click event
 */
var button_filter_onClick = function (evt) {
    evt.preventDefault();
    var $control = evt.currentTarget;
    $control.checked = !$control.checked;
    $control.classList.toggle('dg-checked', $control.checked);
    button_filter_update($control);
};
/**
 * restore control state
 */
var button_filter_restore = function (control, store, action) {
    if (!action)
        return;
    var $control = control.$control;
    var path = action.path;
    $control.checked = path !== undefined && path !== null && path.trim() !== '';
    $control.classList.toggle('dg-checked', $control.checked);
};
/**
 * button filter control
 */
window.datagridControls['button-filter'] = function (control, store, rootSlice) {
    // re-initiate on each store update
    store.subscribe(function () {
        var state = store.getState();
        button_filter_restore(control, store, state.actions[$control.dataID]);
    });
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    $control.checked = $control.getAttribute('data-checked') === 'true';
    var state = store.getState();
    if ($control.dataID !== undefined && state.actions[$control.dataID] !== undefined) {
        // restore control state first time
        button_filter_restore(control, store, state.actions[$control.dataID]);
    }
    else {
        // if initial state is not defined in settings, or via deep link / storage, then
        // try to get it from HTML
        button_filter_update($control);
    }
    $control.removeEventListener('click', button_filter_onClick);
    $control.addEventListener('click', button_filter_onClick);
    // returns an API ..........
    return {
        reset: function () {
            if ($control.dataID && state.initialActions && state.initialActions[$control.dataID] !== undefined) {
                button_filter_restore(control, store, state.initialActions[$control.dataID]);
            }
            else {
                $control.checked = $control.getAttribute('data-checked') === 'true';
                button_filter_update($control);
            }
        },
    };
};


// CONCATENATED MODULE: ./production/src/main/js/domain/controls/reset-button-control.ts
/*
 Reset Button Control

 HTML:
 -----
 <button type="button" data-grid-control="reset-button">Reset</button>
*/
window.datagridControls = window.datagridControls || {};
var reset_button_control_onClick = function (evt) {
    var $control = evt.currentTarget;
    var store = $control.store;
    var state = store.getState();
    if (!state.controls)
        return;
    for (var _i = 0, _a = state.controls; _i < _a.length; _i++) {
        var control = _a[_i];
        var $control_1 = control.$control;
        if (!$control_1.datagrid || !$control_1.datagrid.reset || typeof $control_1.datagrid.reset !== 'function')
            continue;
        $control_1.datagrid.reset();
    }
};
/**
 * reset button control
 */
window.datagridControls['reset-button'] = function (control, store, rootSlice) {
    var $control = control.$control;
    $control.control = control;
    $control.store = store;
    $control.rootSlice = rootSlice;
    $control.dataID = $control.getAttribute('data-id');
    $control.removeEventListener('click', reset_button_control_onClick);
    $control.addEventListener('click', reset_button_control_onClick);
};


// CONCATENATED MODULE: ./production/src/main/css/datagrid.css
// extracted by mini-css-extract-plugin

// CONCATENATED MODULE: ./production/src/main/js/index.ts













/**
 * DataGrid Library
 *
 * Usage examples:
 * -----------------
 * window.datagrid();
 * window.datagrid({ ...settings... });
 * window.datagrid('.path-to-root');
 * window.datagrid('.path-to-root', { ...settings... });
 */
window.datagrid = function (param1, param2) {
    var $roots;
    // settings can be passed in the first or in the second param
    var settings = ((typeof param1 === 'object' ? param1 : param2) || {});
    // no path is passed -> used the whole document
    if (!param1 || typeof param1 === 'object') {
        $roots = [document.body];
    }
    else {
        $roots = document.querySelectorAll(param1);
    }
    for (var i = 0; i < $roots.length; i++) {
        var $root = $roots[i];
        main(settings, $root);
    }
};


/***/ })
/******/ ]);
//# sourceMappingURL=datagrid.js.map