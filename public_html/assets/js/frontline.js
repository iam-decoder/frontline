!function(t,e,n){t(document).ready(function(){e.FES={singleton:{},isStringValidJson:function(t){return"string"!=typeof t||t.length<2?!1:/^[\],:{}\s]*$/.test(t.replace(/\\["\\\/bfnrtu]/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,""))},getMeta:function(n){var r=t(n);if(r.length>0){var a=r.data("cookieString");a.hasOwnProperty("n")&&a.hasOwnProperty("e")&&(e.FES.singleton.encryption=new e.FES.Encryption,e.FES.singleton.encryption.setPublic(a.n,a.e)),e.FES.base_url=r.data("baseUrl"),r.remove()}},Encryption:function(){this.rsa=new RSAKey},generateDataTable:function(t){console.log("generateDataTable: ",t)},contentTransition:function(e,n,r){if(!e||!n)return!1;var a=t(e),o=t(n).css("display","none"),i=(r||800)/2;a.length>0?a.find("> *").each(function(e,n){t(n).fadeOut(i,function(){a.empty(),a.append(o),o.fadeIn(i,function(){t(document).trigger("content_transition_end")})})}):console.warn("could not find "+e+" in the DOM...")}},e.FES.Encryption.prototype.setPublic=function(t,e){return this.rsa.setPublic(t,e)},e.FES.Encryption.prototype.encrypt=function(t){return this.rsa.encrypt(t)},t(document).on("submit","form",function(n){n.preventDefault();var r=t(this),a=r.serializeObject();return a&&"string"==typeof a.password&&a.password.length>0&&(a.password=e.FES.singleton.encryption.encrypt(a.password)),t.ajax({type:r.attr("method"),async:!0,url:r.attr("action"),data:a,dataType:"html",crossDomain:!0,headers:{"X-Requested-With":"XMLHttpRequest"},xhrFields:{withCredentials:!0},success:function(t){e.FES.contentTransition("body",t,600)},error:function(e){"[object String]"===Object.prototype.toString.call(e)?0===e.indexOf("{")&&(e=JSON.parse(e),e.errors&&t.each(e.errors,function(t,e){console.log(e)})):console.log(e)}}),!1}),t(document).on("click","[data-load]",function(n){n.preventDefault();var r=t(this),a=r.data("load");if("string"!=typeof a||a.length<=0)return!1;var o=r.data("with"),i={table:a};return e.FES.isStringValidJson(o)?i=t.extend(i,JSON.parse(o)):"string"==typeof o&&(i.data=o),t.ajax({type:"GET",async:!0,url:e.FES.base_url+"/tabledata",data:i,crossDomain:!0,headers:{"X-Requested-With":"XMLHttpRequest"},xhrFields:{withCredentials:!0},success:function(t){var n="";n=e.FES.isStringValidJson(t)?e.FES.generateDataTable(JSON.parse(t)):t,e.FES.contentTransition("#datatable",n,400)},error:function(t){console.log("error received: ",t)}}),!1})}),t(function(){e.FES.getMeta("#page-meta")})}(jQuery,window);