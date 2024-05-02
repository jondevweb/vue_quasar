<x-main-layout>
    <x-slot name="script">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.1.0/progressbar.min.js" integrity="sha512-EZhmSl/hiKyEHklogkakFnSYa5mWsLmTC4ZfvVzhqYNLPbXKAXsjUYRf2O9OlzQN33H0xBVfGSEIUeqt9astHQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tree-model/1.0.7/TreeModel-min.js" integrity="sha512-bljfMM3WKjO+CSVRpLRW0qAJ8QgBbrKJa5BiH3l9Kemnl5pfKKnHA+QOJSY4Pt/iTYNU/uMGiWLbN3tQUtS7XQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
        var currentComponent = null;
        var path = '{{ $path }}'
        var global = {
            leftMenu: {
                changedNotification: null
            }
        }
        var tExpansionItem = {
        props: ['node', 'level'],
        template:
        `
            <div class=''>
                <template v-for="(val, name, index) in node">
                    <q-expansion-item v-if='val.sub != undefined'
                        v-bind:icon='val.icon'
                        v-bind:label='val.title'
                        v-bind:header-inset-level='level'
                        v-bind:content-inset-level='level+1'
                        default-opened
                        :class="'titem-'+level"
                    >
                        <t-expansion-item v-on:selected='$emit("selected", $event)' v-bind:node='val.sub' v-bind:level='level+1'></t-expansion-item>
                    </q-expansion-item>
                    <q-item clickable v-ripple v-if='val.sub == undefined'
                        active-class='active-menu-link'
                        v-bind:active='val.selected'
                        @click="$emit('selected', val)"
                    >
                        <q-item-section avatar>
                            <q-icon v-bind:name='val.icon' :style='val.style'></q-icon>
                        </q-item-section>
                        <q-item-section :class="'titem-'+level">@{{val.title}}</q-item-section>
                    </q-item>
                    <q-separator v-if='val.separator'></q-separator>
                </template>
            </div>
        `
        }
        var bar = null
        var globalFrame = null
        const QApp = {
            el: '#q-app',
            data: function () {
                return {
                    account: {
                        email: '',
                    },
                    screenlock: {
                        dialog: false,
                        monitor: null,
                        bar: null,
                        askPassword: false,
                        password: '',
                        isPwd: true
                    },
                    left: false,
                    link: 'inbox',
                    leftMenu: [
                        {icon: 'fas fa-tachometer-alt', title: 'Tableau de bord'  , path: 'collecteurs'              , selected: true, separator: true},
                        {icon: 'fas fa-file-contract'           , title: 'Documents'    , path: 'collecteurs/documents'              , selected: false, sub: [
                            {icon: 'fas fa-certificate'         , title: 'Attestations' , path: 'collecteurs/documents/attestations' , selected: false},
                        ]},
                    ],
                    currentComponent: null
                }
            },
            watch: {
                left: function(newValue) {
                    if (typeof global.leftMenu.changedNotification == 'function') global.leftMenu.changedNotification(newValue)
                }
            },
            methods: {
                logout: function() {
                    var vm = this
                    post('/api/v1.0/account/logout', {}, function() {
                        window.location.reload(true)
                    })
                },
                selected: function(node) {
                    var vm = this
                    tree = new TreeModel();
                    root = tree.parse({children: vm.leftMenu});
                    root.walk(function (localNode) {localNode.model.selected = localNode.model.path == node.path});

                    if (vm.currentComponent != node.path.replace(/\//g, '_')) {
                        if (currentComponent != null)
                            currentComponent.unmount()

                        vm.currentComponent = node.path.replace(/\//g, '_')
                        loadComponent(document.getElementById('vue-body'), '/template/collecteurs/'+vm.currentComponent)
                    }
                },
                checkPassword: function() {
                    var vm  = this
                    post('/api/v1.0/account/login', {email: vm.account.email, password: vm.screenlock.password, target:'refresh'}, function(data) {
                        vm.screenlock.askPassword = false
                        vm.screenlock.password    = ''
                        document.querySelector('meta[name="csrf-token"]').setAttribute("content", data.result.token);
                        post('/api/v1.0/integrateur/compte', {}, function(data) {vm.account = data.result})
                        vm.screenlock.monitor.start()
                    })
                }
            },
            mounted: function() {
                var vm  = this
                globalFrame = vm
                const timings = {
                    timeBeforeWarning: 3*60*1000, //in ms
                    timeBeforeLock: 60*1000,      //in ms
                    timeBeforePing: 1*60*1000,    //in ms
                    granularity: 1*1000
                }
                post('/api/v1.0/integrateur/compte', {}, function(data) {vm.account = data.result})
                vm.selected(vm.leftMenu[0])
            }
        }

        const app = Vue.createApp(QApp)
        app.component('t-expansion-item', tExpansionItem)
        app.use(Quasar, quasarConfig)
        Quasar.lang.set(Quasar.lang.fr)
        app.mount('#q-app')
        </script>
    </x-slot>
    <style>
    .titem-0 {
        font-weight: bold;
    }
    .titem-1 {
        font-weight: initial;
    }
    #q-app, body, html {
        width: 100%;
        direction: ltr;
    }
    .error {
        color: red;
        font-size: smaller;
        font-style: italic;
        font-weight: 500;
        margin-top: 4px;
    }
    .active-menu-link {
        color: white;
        background: #F2C037;
    }
    #screenlock .containerlock {
        width: 10vw;
    }
    #screenlock-password .q-dialog__backdrop {
        background: gray;
    }
    .no-padding-top {
        padding-top: 0px !important;
    }
    .short-header {
        width: 50px;

    }
    .short-header div.q-toolbar__title.ellipsis {
        display: none;
    }
    </style>
    <div id='q-app'>
        <template v-if='true'>
        <q-layout view="lHh lpR fFf">

            <q-header elevated class="bg-primary text-white" :class="{'short-header': !left}">
            <q-toolbar>
                <q-btn dense flat round icon="menu" @click="left = !left"></q-btn>

                <q-toolbar-title>
                <q-avatar>
                    <img src="https://cdn.quasar.dev/logo-v2/svg/logo-mono-white.svg">
                </q-avatar>
                TITLE
                <q-btn color="primary" @click='logout' label="Déconnexion"></q-btn>
                </q-toolbar-title>
            </q-toolbar>
            </q-header>
            <q-drawer show-if-above v-model="left" side="left" bordered>
                <q-toolbar>
                    <q-btn dense flat round icon="menu" @click="left = !left"></q-btn>
                    <q-toolbar-title>
                        <q-avatar><img src="https://cdn.quasar.dev/logo-v2/svg/logo-mono-white.svg"></q-avatar>
                    </q-toolbar-title>
                </q-toolbar>
                <q-list bordered class="rounded-borders"><t-expansion-item v-on:selected='selected' :node='leftMenu' :level='0'></t-expansion-item></q-list>
            </q-drawer>
            <q-page-container id='page-container' :class="{'no-padding-top': !left}">
                <div id='vue-body'></div>
            </q-page-container>
        </q-layout>
        <q-dialog v-model="screenlock.dialog" id='screenlock'>
            <q-card>
                <q-card-section>
                <div class="text-h6">Inactivité prolongée</div>
                <div class="text-subtitle3">Le verrouillage automatique est imminent.</div>
                </q-card-section>

                <q-card-section class='fit row wrap justify-center items-start content-start'>
                    <div class="containerlock">
                        <svg
                            xmlns:svg="http://www.w3.org/2000/svg"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="#000000"
                            version="1.1"
                            id="svg10"
                            viewBox="0 0 17 22"
                        >
                            <defs id="defs14"></defs>
                            <path
                                id="gris"
                                style="display:inline;fill:none;fill-rule:evenodd;stroke:#bbbbbb;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:0.733333"
                                d="M 3.4999998,7.5 H 13.5 m 1,0 h -1 v -2 c 0,-2.76 -2.24,-5 -5.0000002,-5 -2.76,0 -5,2.24 -5,5 v 2 h -1 c -1.1,0 -2,0.9 -2,2 v 10 c 0,1.1 0.9,2 2,2 H 14.5 c 1.1,0 2,-0.9 2,-2 v -10 c 0,-1.1 -0.9,-2 -2,-2 z"
                            >
                            </path>
                            <path
                                id="screenlocker-couleur"
                                style="display:inline;fill:none;fill-rule:evenodd;stroke:#ed6a5a;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1"
                                d="m 3.5,7.5 h 10 m 1,0 h -1 v -2 c 0,-2.76 -2.24,-5 -5.0000002,-5 C 5.7399998,0.5 3.5,2.74 3.5,5.5 v 2 h -1 c -1.1,0 -2,0.9 -2,2 v 10 c 0,1.1 0.9,2 2,2 h 12 c 1.1,0 2,-0.9 2,-2 v -10 c 0,-1.1 -0.9,-2 -2,-2 z"
                                fill-opacity="0"
                            >
                            </path>
                        </svg>
                    </div>
                </q-card-section>
                <q-card-actions align="right">
                </q-card-actions>
            </q-card>
        </q-dialog>
        <q-dialog v-model="screenlock.askPassword" id='screenlock-password' persistent>
            <q-card style='width: 40em'>
                <q-card-section>
                <div class="text-h6">Verrouillage session</div>
                <div class="text-subtitle3">Veuillez saisir votre mot de passe</div>
                </q-card-section>

                <q-card-section class='full-width column no-wrap justify-center items-center content-start'>

                    <q-input v-model="account.email" type="email" label="" bottom-slots hint='Courriel' readonly style='width: 20em;'>
                        <template v-slot:prepend><q-icon name="fas fa-envelope"></q-icon></template>
                    </q-input>
                    <q-input v-model="screenlock.password" :type="screenlock.isPwd ? 'password' : 'text'" label="" hint='Mot de passe' :rules='[ val => val.length > 0 || ""  ]' bottom-slots  style='width: 20em;'>
                        <template v-slot:prepend><q-icon name="fas fa-lock"></q-icon></template>
                        <template v-slot:append>
                            <q-icon
                                :name="screenlock.isPwd ? 'visibility_off' : 'visibility'"
                                class="cursor-pointer"
                                @click="screenlock.isPwd = !screenlock.isPwd"
                            ></q-icon>
                            </template>
                    </q-input>

                </q-card-section>
                <q-card-actions align="right">
                    <q-btn @click='checkPassword' size="lg" class="full-width" :disabled="screenlock.password.length == 0" label="Déverrouiller"></q-btn>
                </q-card-actions>
            </q-card>
        </q-dialog>
        </template>
    </div>
    <style>
    #map {
        padding: 5px;
        width: 100%;
        height: 600px;
        box-shadow: 0 0 10px #999;
    }
    </style>
</x-main-layout>
