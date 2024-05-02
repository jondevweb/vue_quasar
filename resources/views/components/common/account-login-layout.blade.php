<x-main-layout>
    <x-slot name="script">
        <script>
        target = '{{ $target }}'
        initialPath = '{{ $path }}'
        const QApp = {
            el: '#q-app',
            data: function () {
                return {
                    email: '',
                    password: '',
                    emailValidity: false,
                    invalid: true,
                    isPwd: true,
                    loggedButNoPermission: {{ $loggedButNoPermission }}
                }
            },
            watch: {
                email: function(newVal) {
                    var vm = this
                    if (newVal.indexOf(' ') != -1)
                        vm.email = newVal.replace(/ /g, '')
                }
            },
            methods: {
                emailValidator: function(email) {
                    this.emailValidity = Utils.validators.email(email)
                    if (this.emailValidity) return true
                    return 'Le courriel n\'est pas valide.'
                },
                onSubmit: function() {
                    var vm = this
                    post('/api/v1.0/account/login', {email: vm.email, password: vm.password, target: '{{ $target }}'}, function(data) {
                        if (data.status)
                            window.location.reload(true)
                        else
                            notify('warning', 'Connexion échouée ; soit le compte n\'existe pas, soit le mot de passe est invalide.')
                    })
                },
                askConfirm: function() {
                    var vm = this
                    this.$q.dialog({
                        title: 'Réinitialisation',
                        message: "Après confirmation, un courriel sera envoyé à votre adresse, courriel contenant les instructions à suivre pour réinitialiser votre mot de passe.<BR/><BR/>Êtes-vous sûr de vouloir réinitialiser votre mot de passe ?",
                        cancel: true,
                        persistent: true,
                        html: true
                    }).onOk(() => {
                        post('/api/v1.0/recup/resetpassword', {email: vm.email, target: target}, function(data) {
                            if (data.status)
                                vm.$q.dialog({
                                    title: '',
                                    persistent: true,
                                    html: true,
                                    message: 'Si le compte indiqué existe bien, nous vous invitons alors à consulter sa boîte de réception, et éventuellement son dossier spam.'
                                })
                            else if (data.message.indexOf('reset throttled') >= 0)
                                notify('warning', 'Trop de tentatives on été réalisées.<BR/>Veuillez réessayer dans 2 minutes.')
                            else
                                notify('negative', 'Une erreur interne s\'est produite, et nos équipes ont été averties.<BR/>Veuillez reessayer dans quelques minutes et le cas échéant nous contacter.')
                        })
                    })
                }
            },
            mounted: function() {
                var vm = this
                if (vm.loggedButNoPermission) notify('negative', 'Authentification réussie mais la page souhaitée ne vous est pas autorisée ; vous avez été déconnecté.')
            }
        }
        const app = Vue.createApp(QApp)
        app.use(Quasar, quasarConfig)
        Quasar.lang.set(Quasar.lang.fr)
        app.mount('#q-app')
        </script>
    </x-slot>
    {{ $slot }}
</x-main-layout>