/*
YUI 3.14.1 (build 63049cb)
Copyright 2013 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("series-spline-stacked",function(e,t){e.StackedSplineSeries=e.Base.create("stackedSplineSeries",e.SplineSeries,[e.StackingUtil],{setAreaData:function(){e.StackedSplineSeries.superclass.setAreaData.apply(this),this._stackCoordinates.apply(this)}},{ATTRS:{type:{value:"stackedSpline"}}})},"3.14.1",{requires:["series-stacked","series-spline"]});
