(function () {
  var luroute = (function () {
    var router = {
      routes: DUMMY_ROUTES,
      origin: 'DUMMY_ORIGIN',

      getRouteByName: function (name) {
        for (var key in this.routes) {
          if (this.routes.hasOwnProperty(key) && this.routes[key].name === name) {
            return this.routes[key];
          }
        }
      },

      getRouteByAction: function (action) {
        for (var key in this.routes) {
          if (this.routes.hasOwnProperty(key) && this.routes[key].action === action) {
            return this.routes[key];
          }
        }
      },

      replaceRouteParameters: function (uri, parameters) {
        var regex = new RegExp(/({([^}]*)})/, 'g');
        var matches = uri.match(regex);

        if (!matches) {
          return uri;
        }

        if (this.isArray(parameters)) {
          for (var key in parameters) {
            if (parameters.hasOwnProperty(key)) {
              uri = this.replaceRouteParameter(uri, /{([^}]*)}/, parameters[key]);
            }
          }
        } else {
          for (var key in parameters) {
            if (parameters.hasOwnProperty(key)) {
              var routeKeyRegex = new RegExp('{' + key + ':([^}]*)}');
              uri = this.replaceRouteParameter(uri, routeKeyRegex, parameters[key]);
            }
          }
        }

        return uri;
      },

      replaceRouteParameter: function (uri, regex, parameter) {
        if (parameter instanceof Object && parameter.hasOwnProperty('id')) {
          parameter = parameter.id;
        }

        return uri.replace(regex, parameter);
      },

      addOriginUrl: function (uri, absolute) {
        if (!absolute) {
          return uri;
        }

        return this.origin.replace(/[\/]+$/, '') + '/' + uri.replace(/^[\/]+/, '');
      },

      isArray: function (item) {
        if (typeof Array.isArray === 'undefined') {
          return Object.prototype.toString.call(item) === '[object Array]';
        }

        return Array.isArray(item);
      }
    };

    return {
      route: function (name, parameters, absolute) {
        var route = router.getRouteByName(name);
        parameters = parameters || [];

        if (!route) {
          return undefined;
        }

        var uri = router.replaceRouteParameters(route.uri, parameters);

        return router.addOriginUrl(uri, absolute);
      },

      action: function (name, parameters, absolute) {
        var route = router.getRouteByAction(name);
        parameters = parameters || [];

        if (!route) {
          return undefined;
        }

        var uri = router.replaceRouteParameters(route.uri, parameters);

        return router.addOriginUrl(uri, absolute);
      },
    };
  })();

  if (typeof module === 'object' && module.exports) {
    module.exports = luroute;
  } else if (typeof define === 'function' && define.amd) {
    define(function () {
      return luroute;
    });
  } else {
    window.DUMMY_NAMESPACE = luroute;
  }
})();
