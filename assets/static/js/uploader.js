/*global angular */
'use strict';

/**
 * The main app module
 * @name app
 * @type {angular.Module}
 */
var app = angular.module('app', ['flow'])
.config(['flowFactoryProvider', function (flowFactoryProvider) {
  flowFactoryProvider.defaults = {
    target: '/api/broadcast/upload',
    permanentErrors: [404, 500, 501],
    maxChunkRetries: 1,
    chunkRetryInterval: 5000,
    simultaneousUploads: 2,
    singleFile: true,
    testChunks: false
  };
  //flowFactoryProvider.on('catchAll', function (event) {
    //console.log('catchAll', arguments);
  //});

  // Can be used with different implementations of Flow.js
  // flowFactoryProvider.factory = fustyFlowFactory;
}]);

app.controller("uploadCtrl", function($scope, $window) {
  $scope.selectFile = true;

  $scope.visOptions = [
    { name: 'Public', value: 'public' }, 
    { name: 'Private', value: 'private' }
  ];

  $scope.vis = $scope.visOptions[0].value;

  $scope.$on('flow::fileAdded', function (event, $flow, flowFile) {
    /* Verify file is a media file first */

    $scope.selectFile = false;
    $scope.setProperties = true;
  });

  $scope.$on('flow::fileSuccess', function (event, $flow, flowFile) {
    alert("Video successfully uploaded, ready for processing");
    $window.location.href = '/';
  });

  $scope.onUploadClicked = function($flow) {
    var title = $scope.title;
    var desc = $scope.desc;
    var vis = $scope.vis;

    if(!title) {
      alert("Please add a video title");
      return;
    }
    if(!desc) {
      alert("Please add a video description");
      return;
    }
    if(!vis) {
      alert("Please select a visibility option");
      return;
    }

    $scope.inputDisabled = true;

    $flow.opts.query = {
      'title': title,
      'desc': desc,
      'vis': vis
    };

    $flow.resume()
  }
});