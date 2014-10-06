var data=SidelinesRestApi,
    sidelines_url=data.remoteUrl,
    sidelines_ver=data.version,
    sidelines_post_url=data.permalink,
    sidelineslib=lightningjs.require("sidelineslib","//"+sidelines_url+"/static/js/sidelines-sdk.min.js")
    sidelineslib("identify",SidelinesRestApi.pubCode),
    sidelineslib("enableAnalytics"),
    sidelineslib("display", {remote:sidelines_url,targetContainerId:"sidelines_discussion"});
