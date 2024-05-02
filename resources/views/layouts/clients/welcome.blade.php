<x-main-layout>
    <x-slot name="quasarconfig">
        <script>
            quasarConfig = {
            config: {
                brand: {
                    primary: '#003f6e',
                    secondary: '#003f6e',
                    accent: '#003f6e',

                    dark: '#1d1d1d',

                    positive: '#21BA45',
                    negative: '#C10015',
                    info: '#b1dc4c',
                    warning: '#ffcc03'
                }
            }
            }
        </script>
    </x-slot>
    <x-slot name="script">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tree-model/1.0.7/TreeModel-min.js" integrity="sha512-bljfMM3WKjO+CSVRpLRW0qAJ8QgBbrKJa5BiH3l9Kemnl5pfKKnHA+QOJSY4Pt/iTYNU/uMGiWLbN3tQUtS7XQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js" integrity="sha512-TZlMGFY9xKj38t/5m2FzJ+RM/aD5alMHDe26p0mYUMoCF5G7ibfHUQILq0qQPV3wlsnCwL+TPRNK4vIWGLOkUQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.1.0/progressbar.min.js" integrity="sha512-EZhmSl/hiKyEHklogkakFnSYa5mWsLmTC4ZfvVzhqYNLPbXKAXsjUYRf2O9OlzQN33H0xBVfGSEIUeqt9astHQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <style>
            .short-inset .q-expansion-item__content {
                padding-left: 30px !important;
            }
        </style>
        <script>
        var appGlobal = {}
        var currentComponent = null;
        var pointcollecte = {
            currentValue: [],
            currentValueFull: [],
            changedNotification: null,
            currentClient: null
        }
        var appVM = null;
        var path = '{{ $path }}'
        var tExpansionItem = {
            props: ['node', 'level', 'showAll'],
            template:
            `
                <div class=''>
                    <template v-for="(val, name, index) in node">
                        <q-expansion-item v-if='val.children != undefined && (!val.hidded || showAll)'
                            v-bind:icon='val.icon'
                            v-bind:label='val.title'
                            v-bind:header-inset-level='0'
                            v-bind:content-inset-level='1'
                            v-model='val.opened'
                            :class="{'short-inset': level == 1}"
                            :show-all='showAll'
                        >
                            <t-expansion-item v-on:selected='$emit("selected", $event)' v-bind:node='val.children' v-bind:level='level+1' :show-all='showAll'></t-expansion-item>
                        </q-expansion-item>
                        <q-item clickable v-ripple v-if='val.children == undefined && (!val.hidded || showAll)'
                            active-class='active-menu-link'
                            v-bind:active='val.selected'
                            @click="$emit('selected', val)"
                        >
                            <q-item-section v-if='val.icon != undefined  && (!val.hidded || showAll)' avatar>
                                <q-icon v-bind:name='val.icon' :style='val.style' :color='val.selected ? "#b1dc4c" : "white"'></q-icon>
                            </q-item-section>
                            <q-item-section :style='{color: (val.selected ? "#b1dc4c" : "white")}'>@{{val.title}}</q-item-section>
                        </q-item>
                        <q-separator inset v-if='val.separator'></q-separator>
                    </template>
                </div>
            `
        }
        var globalShowAll = false
        var QApp = {
            el: '#q-app',
            data: function () {
                return {
                    allowedIps: ['192.168.1.17', '109.190.64.160'],
                    left: false,
                    showAll: false,
                    link: 'inbox',
                    panelVisible: true,
                    visible: true,
                    leftMenu: [
                        {icon: 'home'          , title: 'Tableau de bord'      , path: 'clients/welcome'    , selected: true, separator: true},
                        {icon: 'settings'      , title: 'Informations du site' , path: 'clients/infos'      , selected: false, separator: false, children: [
                            {title: 'Informations générales'            , path: 'clients/infos_generales'        , selected: false},
                            {title: 'Contacts associés'                 , path: 'clients/infos_contacts'         , selected: false},
                            {title: 'Conditions d\'accès'               , path: 'clients/infos_acces'            , selected: false},
                        ]},
                        {icon: 'fas fa-truck'    , title: 'Informations des collectes', path: 'clients/collectes'              , selected: false, separator: false, children: [
                            {                     title: 'Calendrier'                , path: 'clients/collectes_calendrier' , selected: false},
                            {                     title: 'Données brutes'            , path: 'clients/collectes_donnees'    , selected: false},
                            {                     title: 'Documents de traçabilité'  , path: 'clients/collectes_tracabilite', selected: false, children: [
                                {                     title: 'Attestation de valorisation' , path: 'clients/documents_attestation', selected: false},
                                {                     title: 'Registre de suivi de déchets', path: 'clients/documents_registre'   , selected: false},
                            ]},
                        ], opened: true},
                    ],
                    account: {
                        model: {
                        },
                        string: '',
                        dialog: false,
                        selected: null,
                        pointcollectsFiltered: []
                    },
                    parametres: {
                        model: {},
                        string: '',
                        dialog: false,
                        tree: {
                            selected: [],
                            ticked: [],
                            expanded: [],
                            simple: [
                                {
                                    id: '1-0-0',
                                    label: 'Notifications',
                                    children: [
                                        {id: '1-1-0', label: 'disponibilité des documents de passage'    , comment:'Bons de passage et de pesée, et mise à jour du registre de déchet'},
                                        {id: '1-2-0', label: 'disponibilité des documents de destruction', comment:'Bons de recyclage, destruction, et bordereaux de suivi de déchet'},
                                        {id: '1-3-0', label: 'disponibilité des documents annuels', disabled: true},
                                    ]
                                }
                            ]
                        }
                    },
                    headBar: {
                        demandeCollecte: {
                            dialog: false,
                            motif: null,
                            message: '',
                            motifOptions:[
                                {value: "Demande d'une collecte", disable: false}, {value: 'Autre sujet', disable: false}
                            ]
                        }
                    }
                }
            },
            watch: {
                'account.selected': function(newVal, oldVal) {
                    if (newVal == null) {
                        pointcollecte.currentValue  = []
                        pointcollecte.currentValueFull = []
                        pointcollecte.currentClient = null
                    }
                    else {
                        pointcollecte.currentValue  = [newVal.id]
                        pointcollecte.currentValueFull = [newVal]
                        pointcollecte.currentClient = newVal.client_id
                    }
                    if (typeof pointcollecte.changedNotification == 'function') pointcollecte.changedNotification(newVal)
                },
                'parametres.dialog': function(newVal, oldVal) {
                    var vm = this
                    vm.parametres.tree.expanded = ['1-0-0']
                    vm.parametres.tree.ticked   = function(){
                        var retour = []
                        if (vm.parametres.model.notification_enlevement) retour.push('1-2-0')
                        if (vm.parametres.model.notification_pesee) retour.push('1-1-0')
                        return retour
                    }()
                },
                'headBar.demandeCollecte.dialog': function(newVal, oldVal) {
                    var vm = this
                    if (newVal && vm.visible)
                        vm.headBar.demandeCollecte.message = "\n\n\n\n\n\nPS : il s'agit du site « "+vm.account.selected.nom+" »"
                },
                'visible': function(newVal) {
                    var vm = this
                    if(newVal) {
                        vm.headBar.demandeCollecte.motifOptions[0].disable = false
                        vm.headBar.demandeCollecte.motif = vm.headBar.demandeCollecte.motifOptions[0].value
                        vm.headBar.demandeCollecte.message = "\n\n\n\n\n\nPS : il s'agit du site « "+vm.account.selected.nom+" »"
                    }else{
                        vm.headBar.demandeCollecte.motifOptions[0].disable = true
                        vm.headBar.demandeCollecte.motif = vm.headBar.demandeCollecte.motifOptions[1].value
                        vm.headBar.demandeCollecte.message = ""
                    }
                },
                showAll: function(newVal) {
                    var vm = this
                    globalShowAll = newVal
                }
            },
            computed: {
                'parametres_tree_expanded': {
                    get: function() { return ['1-0-0'] },
                    set: function() {},
                },
                'parametres_tree_ticked':  {
                    get: function() {
                        var vm = this
                        var retour = []
                        if (vm.parametres.model.notification_enlevement) retour.push('1-2-0')
                        if (vm.parametres.model.notification_pesee) retour.push('1-1-0')
                        return retour
                    },
                    set: function(newValue) {
                        var vm = this
                        vm.parametres.model.notification_enlevement = vm.parametres.model.notification_pesee = 0
                        if (newValue.indexOf('1-2-0') >= 0) vm.parametres.model.notification_enlevement = 1
                        if (newValue.indexOf('1-1-0') >= 0) vm.parametres.model.notification_pesee = 1
                    }
                },
            },
            methods: {
                envoieDemande: function() {
                    var vm = this
                    post('/api/v1.0/client/compte/contact', {
                                                                pointcollecte_id: vm.visible ? vm.account.selected.id : null,
                                                                motif: vm.headBar.demandeCollecte.motif,
                                                                message: vm.headBar.demandeCollecte.message
                                                            }, function(data) {
                        vm.headBar.demandeCollecte.dialog = false
                        notify('positive', 'Message envoyé à votre prestataire')
                    })
                },
                showPointcollecteSelector: function(show) {
                    var vm = this
                    vm.visible = show === true
                },
                logout: function() {
                    var vm = this
                    post('/api/v1.0/account/logout', {}, function() {
                        window.location.reload(true)
                    })
                },
                selected: function(node, noHistoryChange) {
                    var vm = this
                    tree = new TreeModel();
                    root = tree.parse({children: vm.leftMenu});
                    root.walk(function (localNode) {localNode.model.selected = localNode.model.path == node.path});
                    if (vm.currentComponent != node.path.replace(/\//g, '_')) {
                        if (currentComponent != null)
                            currentComponent.unmount()

                        vm.currentComponent = node.path.replace(/\//g, '_')
                        pointcollecte.changedNotification = null
                        vm.showPointcollecteSelector(false)
                        loadComponent(document.getElementById('vue-body'), '/template/clients/'+vm.currentComponent)
                        if (noHistoryChange !== true)
                            history.pushState({path: node.path}, "")
                    }
                },
                updateContact:function(contact) {
                    var vm = this
                    vm.$refs.account.validate().then(success => {
                        if (success) {
                            var preprocess = function(a) {
                                delete a.clients
                                return a
                            }
                            var params = vm.diff(JSON.stringify(contact), vm.account.string, preprocess)
                            if (Object.keys(params).length == 0) return
                            post('/api/v1.0/client/compte/update', params, function(data) {
                                vm.loadContact()
                                notify('positive', 'Mise à jour effectuée')
                                vm.account.dialog = false
                            })
                        }else{
                            notify('notice', 'Veuillez compléter le formulaire avant de le sauver.')
                        }
                    })
                },
                resetContact: function() {
                    var vm = this
                    vm.account.model = JSON.parse(vm.contact.string)
                },
                loadContact: function(contact) {
                    var vm = this
                    post('/api/v1.0/client/compte', {}, function(data) {
                        console.log(data.result)
                        vm.account.model = data.result
                        vm.account.string = JSON.stringify(vm.account.model)
                        vm.account.pointcollectsFiltered = JSON.parse(JSON.stringify(vm.account.model.pointcollectes))
                        vm.account.selected = vm.account.model.pointcollectes[0]
                        vm.parametres.model = data.result.parametres
                        vm.selected(vm.leftMenu[0])
                    })
                },
                updatePassword: function(oldPassword, newPassword) {
                    var vm = this
                    post('/api/v1.0/client/compte/password', {password: newPassword, oldPassword: oldPassword}, function(data) {
                        if (data.status) {
                            notify('positive', 'Mot de passe mis à jour.');
                            vm.$refs.account.pswdDialog = false
                        }else
                            notify('negative', 'L\'ancien mot de passe ne semble pas correcte.');
                    }, function(error) {
                        if (error.status == 422)
                            notify('negative', 'Votre mot de passe semble avoir déjà fuité sur Internet ; vous ne pouvez donc pas l\'utiliser ici.');
                        else
                            notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
                        console.debug(error)
                    })
                },
                updateParametres:function(parametres) {
                    var vm = this
                    var params = {
                        notification_pesee: 0,
                        notification_enlevement: 0
                    }
                    if (parametres.ticked.indexOf('1-2-0') >= 0) params.notification_enlevement = 1
                    if (parametres.ticked.indexOf('1-1-0') >= 0) params.notification_pesee      = 1
                    post('/api/v1.0/client/compte/parametres/update', params, function(data) {
                        notify('positive', 'Mise à jour effectuée')
                        vm.parametres.dialog = false
                        vm.parametres.model.notification_enlevement = params.notification_enlevement
                        vm.parametres.model.notification_pesee      = params.notification_pesee
                    })
                },
                filterFn (val, update) {
                    var vm = this
                    if (val === '') {
                        update(() => {
                            vm.account.pointcollectesFiltered = vm.account.model.pointcollectes
                        })
                        return
                    }
                    update(() => {
                        const needle = val.toLowerCase()
                        vm.account.pointcollectesFiltered = vm.account.pointcollectesFiltered.filter(v => v.nom.toLowerCase().indexOf(needle) > -1)
                    })
                },
                toggleLeftDrawer: function() {
                    var vm = this
                    vm.left = !vm.left
                },
                reload: function() {
                    window.location.reload(true)
                }
            },
            mounted: function() {
                var vm = this
                appVM  = this
                vm.loadContact()
                vm.headBar.demandeCollecte.motif = vm.headBar.demandeCollecte.motifOptions[0].value
            }
        }
        Utils.loader(QApp)
        app = Vue.createApp(QApp)
        app.component('t-expansion-item', tExpansionItem)
        app.use(Quasar, quasarConfig)
        Quasar.lang.set(Quasar.lang.fr)
        Object.keys(VueComponents).forEach(function(value, idx, tab) {
            app.component(value, VueComponents[value])
        })
        Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {
            app.component('business-'+value, VueBusinessComponents[value])
        })
        app.mount('#q-app')
        function switchPage(path, noHistoryChange) {
            appVM.selected({path: path}, noHistoryChange)
        }
        window.onpopstate = function(event) {
            if (typeof event.state == 'object')
                if (event.state.path != undefined)
                    switchPage(event.state.path, true)
        }
        </script>
    </x-slot>
    <style>
    html, body {
        background-color: #f2efef;
    }
    #userForm i {
        color: #003f6e;
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
        color: #003f6e;
        font-weight: bolder;
    }
    header {
        background-color: white !important;
    }
    #leftpanel {
        background-color: #003f6e;
    }
    #leftpanel *{
        color: white;
    }
    #leftpanel hr.q-separator{
        background-color: white;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        height: 0.05rem;
        width: 80%;
        margin-left: auto;
        margin-right: auto;
    }
    #q-app header * {
        color: #003f6e;
    }
    aside {
        width: 300px !important;
    }
    .logowrapper {
        min-width: 300px;
        width: 300px;
        text-align: center;
        border-right-width: 1px;
        border-right-color: black;
        border-right-style: inset;
    }
    .notificationicon {
        color: #003f6e;
    }
    #passwdComplexity {
        margin-bottom: 0.5rem;
        height: 0.1rem;
        position: absolute;
        top: 0;
        width: 100%;
    }
    </style>
    <style>
    /*pour la bulle d'aide*/
        .araccourcir {
            overflow: auto;
            white-space: initial;
        }
        #chatwrapper {
            display: none;
            position: fixed;
            bottom: 0.1em;
            right: 0.1em;
            overflow: hidden;
            z-index: 99999999;
        }
        #chatframe {
            animation: frameClosed .25s;
            width: 6em;
            height: 6em;
            border-radius: 1em 1em 1em 1em;
            border: none;
            overflow: hidden;
            background-color: #FFFD;
        }
        #chatframe.opened {
            animation: frameOpened .25s;
            width: 20rem;
            height: 20rem;
            height: 23rem;
            border-top-left-radius: 0rem;
            border-top-right-radius: 0rem;
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
        }
        #chatwrapper.opened {
            width: 20rem;
            height: 20rem;
            height: 23rem;
        }
        #chatframe.reduced {
            width: 1.5em;
            height: 1.5em;
            border-radius: 1rem;
        }
        #chatwrapper.reduced {
            width: 1.5em;
            height: 1.5em;
            border-radius: 1rem;
        }
        @keyframes frameOpened {
            from {
            transform: scale(0);
            }
            to {
            transform: scale(1);
            }
        }
        @keyframes frameClosed {
            from {
            transform: scale(1);
            }
            to {
            transform: scale(0);
            }
        }
    </style>
    <div id='q-app'>
        <template v-if='true'>
        <q-layout view="hHh lpR fFf">
            <q-header elevated style='height: 4em;'>
                <q-toolbar class='fit row wrap justify-start items-start content-center' style='padding-left: 0;'>
                    <q-btn
                        flat
                        dense
                        round
                        @click="toggleLeftDrawer"
                        aria-label="Menu"
                        icon="menu"
                        class="q-mr-sm"
                        v-show='!panelVisible'
                    ></q-btn>
                    <div class='logowrapper'>
                        <q-img
                            src="/image/ID-icionrecycle-logo.svg"
                            spinner-color="white"
                            style="weight: 140px; max-width: 150px"
                            mode='fit'
                            @click='reload'
                            class='cursor-pointer'
                        ></q-img>
                    </div>
                    <q-space></q-space>
                    <q-select
                        filled
                        v-model="account.selected"
                        use-input
                        input-debounce="0"
                        label="Point de collecte"
                        :options="account.pointcollectsFiltered"
                        @filter="filterFn"
                        behavior="menu"
                        style='background-color: #e5f4be;'
                        option-label='nom'
                        v-if='visible'
                    >
                        <template v-slot:no-option>
                        <q-item>
                            <q-item-section class="text-grey">
                            Aucun site de trouvé
                            </q-item-section>
                        </q-item>
                        </template>
                    </q-select>
                    <q-space></q-space>
                    <div class="q-gutter-sm row items-center no-wrap">
                        <q-btn round dense flat>
                            <q-img src="/image/email.svg" spinner-color="white" mode='fit' @click='headBar.demandeCollecte.dialog = true'></q-img>
                        </q-btn>
                        <q-btn round dense flat v-if='showAll'>
                            <q-img src="/image/email.svg" spinner-color="white" mode='fit'></q-img>
                            <q-tooltip>Messages</q-tooltip>
                        </q-btn>
                        <q-btn round dense flat icon="notifications" class='notificationicon' v-if='showAll'>
                            <q-badge color="red" text-color="white" floating>2</q-badge>
                            <q-tooltip>Notifications</q-tooltip>
                        </q-btn>
                        <q-btn-dropdown round flat color="primary" icon='img:/image/user.svg'>
                            <template v-slot:label>
                                <div class="row items-center no-wrap">
                                <div class="text-center" style='font-weight: normal;font-size: smaller;line-height: normal;'>
                                    @{{account.model.prenom}}<br>@{{account.model.nom}}
                                </div>
                                </div>
                            </template>
                            <q-list>
                                <q-item clickable v-close-popup @click="account.dialog = true">
                                <q-item-section>
                                    <q-item-label>Mes informations</q-item-label>
                                </q-item-section>
                                </q-item>

                                <!--q-item clickable v-close-popup @click="parametres.dialog = true">
                                <q-item-section>
                                    <q-item-label>Mes paramètres</q-item-label>
                                </q-item-section>
                                </q-item-->

                                <q-separator></q-separator>
                                <q-item clickable v-close-popup href='https://triethic.fr/wp-content/uploads/2022/02/Ici-on-recycle.pdf'>
                                <q-item-section>
                                    <q-item-label>Documentation</q-item-label>
                                </q-item-section>
                                </q-item>
                                <q-separator></q-separator>
                                <q-item clickable v-close-popup @click="logout">
                                <q-item-section>
                                    <q-item-label>Déconnexion</q-item-label>
                                </q-item-section>
                                </q-item>
                            </q-list>
                        </q-btn-dropdown>
                    </div>
                </q-toolbar>
            </q-header>
            <q-drawer show-if-above v-model="left" side="left" bordered id='leftpanel' @on-layout='state => panelVisible = state'>
                <q-scroll-area class="fit">
                    <q-list bordered class="rounded-borders"><t-expansion-item v-on:selected='selected' :node='leftMenu' :level='0' :show-all='showAll'></t-expansion-item></q-list>
                </q-scroll-area>
            </q-drawer>
            <q-page-container>
                <q-dialog v-model="account.dialog" persistent>
                    <ghost-wrapper v-model:one='account.model'>
                        <template v-slot='props'>
                            <q-card style='width: 50vw;'>
                                <q-card-section v-if='props.clones.one != null'>
                                    <business-contact-edit view-type="user" ref='account' v-model='props.clones.one' @update-password='updatePassword'></business-contact-edit>
                                </q-card-section>
                                <q-card-actions align="right">
                                    <q-btn color='primary' :disable='props.identical' label="Enregistrer"   @click='updateContact(props.clones.one)'></q-btn>
                                    <q-btn type="reset"    :disable='props.identical' label="Réinitialiser" @click='props.reset()'></q-btn>
                                    <q-btn                                            label="Fermer"        v-close-popup></q-btn>
                                </q-card-actions>
                            </q-card>
                        </template>
                    </ghost-wrapper>
                </q-dialog>
                <q-dialog v-model="parametres.dialog" persistent>
                    <ghost-wrapper v-model:one='parametres.tree'>
                        <template v-slot='props'>
                            <q-card style='width: 50vw;'>
                                <q-card-section>
                                    <div class="text-h6">Paramètres</div>
                                </q-card-section>

                                <q-separator inset></q-separator>
                                <q-card-section v-if='props.clones.one != null'>
                                    <q-tree class="col-12 col-sm-6"
                                    :nodes="props.clones.one.simple"
                                    node-key="id"
                                    tick-strategy="leaf"
                                    v-model:ticked="props.clones.one.ticked"
                                    v-model:expanded="props.clones.one.expanded"
                                    default-expand-all
                                    >
                                        <template v-slot:default-body="prop">
                                            <div>
                                                <span class="text-weight-bold text-italic">@{{prop.node.comment}}</span>
                                            </div>
                                        </template>
                                    </q-tree>
                                </q-card-section>
                                <q-card-actions align="right">
                                    <q-btn color='primary' :disable='props.identical' label="Enregistrer"   @click='updateParametres(props.clones.one)'></q-btn>
                                    <q-btn type="reset"    :disable='props.identical' label="Réinitialiser" @click='props.reset()'></q-btn>
                                    <q-btn                                            label="Fermer"        v-close-popup></q-btn>
                                </q-card-actions>
                            </q-card>
                        </template>
                    </ghost-wrapper>
                </q-dialog>
                <q-dialog v-model="headBar.demandeCollecte.dialog" persistent>
                    <ghost-wrapper v-model:one='parametres.tree'>
                        <template v-slot='props'>
                            <q-card style='width: 50vw;'>
                                <q-card-section>
                                    <div class="text-h6">Quelle est votre demande ?</div>
                                </q-card-section>
                                <q-separator inset></q-separator>
                                <q-card-section>
                                    <q-select option-label='value' option-disable='disable' emit-value map-options v-model="headBar.demandeCollecte.motif" :options="headBar.demandeCollecte.motifOptions" label="Motif"></q-select>
                                </q-card-section>
                                <q-card-section>
                                    <q-input v-model="headBar.demandeCollecte.message" label="Explications détaillées" filled maxlength='1024' rows='10' type="textarea"></q-input>
                                </q-card-section>
                                <q-separator inset></q-separator>
                                <q-card-actions align="right">
                                    <q-btn color='primary' :disable='headBar.demandeCollecte.message.length == 0' label="Envoyer"   @click='envoieDemande'></q-btn>
                                    <q-btn                                                                        label="Fermer"    v-close-popup></q-btn>
                                </q-card-actions>
                            </q-card>
                        </template>
                    </ghost-wrapper>
                </q-dialog>
                <div id='vue-body'></div>
            </q-page-container>
        </q-layout>
        </template>
    </div>
</x-main-layout>
