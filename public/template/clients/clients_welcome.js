appVM.showPointcollecteSelector(true)
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
        }
    },
    watch: {
    },
    computed: {
    },
    methods: {
    },
    mounted: function() {
        var vm = this
    }
}
Utils.loader(currentComponentConfig)
currentComponent = Vue.createApp(currentComponentConfig)
Utils.filter(currentComponent)
currentComponent.use(Quasar, quasarConfig)
Object.keys(VueComponents).forEach(function(value, idx, tab) {currentComponent.component(value, VueComponents[value])})
Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {currentComponent.component('business-'+value, VueBusinessComponents[value])})
currentComponentVM = currentComponent.mount('#TOTORO')
