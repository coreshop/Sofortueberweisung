pimcore.registerNS("pimcore.plugin.sofortueberweisung");

pimcore.plugin.sofortueberweisung = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.sofortueberweisung";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){
        // alert("Example Ready!");
    }
});

var sofortueberweisungPlugin = new pimcore.plugin.sofortueberweisung();

