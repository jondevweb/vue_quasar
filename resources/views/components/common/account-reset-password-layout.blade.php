<x-main-layout>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js" integrity="sha512-TZlMGFY9xKj38t/5m2FzJ+RM/aD5alMHDe26p0mYUMoCF5G7ibfHUQILq0qQPV3wlsnCwL+TPRNK4vIWGLOkUQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.1.0/progressbar.min.js" integrity="sha512-EZhmSl/hiKyEHklogkakFnSYa5mWsLmTC4ZfvVzhqYNLPbXKAXsjUYRf2O9OlzQN33H0xBVfGSEIUeqt9astHQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <x-slot name="script">
        <script>
        var token = '{{ $token }}'
        var target = '{{ $target }}'
        var bar;
        /*
        Quasar.lang.set(Quasar.lang.fr)
        Vue.component('ValidationProvider', VeeValidate.ValidationProvider);
        Vue.component('ValidationObserver', VeeValidate.ValidationObserver);
        VeeValidate.extend('password', {
            validate: function(value, values) {
                result = zxcvbn(value, values)
                return result.score >= 3
                },
            message: "Mot de passe trop faible. Conseil: 8 charactères, des chiffres/lettres/majuscules/symboles."
        });
        VeeValidate.localize('fr', fr)
        */
        const QApp = {
            el: '#q-app',
            data: function () {
                return {
                    email: '',
                    password: '',
                    displayPassword : false,
                    passwordComplexEnough: false,
                    wrongtoken: false,
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
                    post('/api/v1.0/recup', {email: this.email, password: this.password, password_confirmation: this.password, token: token, target: target}, function(data) {
                        if (data.status) {
                            vm.$q.dialog({
                                title: 'Mot de passe changé',
                                message: "Vous allez maintenant être redirigé vers la page d'accueil pour vous connecter.",
                                persistent: true,
                                html: true
                            }).onOk(() => {location = location.protocol+'//'+location.host+'/'+target})
                        }
                        else {
                            if (data.message.indexOf('invalid token') >= 0) {
                                vm.$q.dialog({
                                    title: 'Jeton de réinitialisation invalide',
                                    message: `<p>Trop de temps s'est écoulé depuis l'envoi du mail de réinitalisation ou le jeton a déjà été utilisé.</p>
                                              <p>Il va vous falloir demander à nouveau une réinitalisation de votre mot de passe.</p>
                                              <p>Vous allez maintenant être redirigé vers la page d'accueil pour cela.</p>`,
                                    persistent: true,
                                    html: true
                                }).onOk(() => {location = location.protocol+'//'+location.host+'/clients'})
                            }
                            else
                                notify('negative', 'Une erreur interne s\'est produite, et nos équipes ont été averties.<BR/>Veuillez reessayer dans quelques minutes et le cas échéant nous contacter.')
                        }
                    })
                },
                redirectClosed: function() {
                    console.debug('On redirige !')
                }
            },
            watch: {
                password: function(newVal, oldVal) {
                    var vm = this
                    result = zxcvbn(newVal, [this.email, this.email.split('@')])
                    vm.passwordComplexEnough = result.score >= 3
                    bar.stop()
                    bar.animate(result.score/4);
                }
            },
            mounted: function() {
                bar = new ProgressBar.Line('#passwdComplexity', {
                    strokeWidth: 4,
                    easing: 'easeInOut',
                    duration: 250,
                    color: '#FF0000',
                    trailColor: '#eee',
                    trailWidth: 1,
                    svgStyle: {width: '100%', height: '100%'},
                    from: {color: '#FF0000'},
                    to: {color: '#5aed6a'},
                    step: (state, bar) => {
                        bar.path.setAttribute('stroke', state.color);
                    }
                });
            }
        }
        const app = Vue.createApp(QApp)
        app.use(Quasar, quasarConfig)     
        Quasar.lang.set(Quasar.lang.fr)       
        app.mount('#q-app')
        </script>
    </x-slot>
    <style>
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
    #passwdComplexity {
        margin-bottom: 0.5rem;
        height: 0.1rem;
        position: absolute;
        top: 0;
        width: 100%;
    }
    </style>
    <div id='q-app'>
        <template v-if='true'>
            <q-layout view="hHh lpR fFf">
                <q-page-container class="window-height window-width row justify-center items-center">
                <div class="column">
                    <div class="row">
                        <h5 class="text-h5 q-my-md">ICI On Recycle</h5>
                    </div>
                    <div class="row">
                        <form @submit.prevent="onSubmit">
                            <q-card square bordered class="q-pa-lg shadow-1" style='min-width: 20rem;'>
                                <q-card-section>
                                    <q-input v-model="email" type="email" label="" bottom-slots hint='Courriel' :rules="[ val => emailValidator(val) || 'Courriel invalide']">
                                        <template v-slot:prepend><q-icon name="fas fa-envelope"></q-icon></template>
                                    </q-input>
                                    <q-input v-model="password" :type="displayPassword ? 'text' : 'password'" label="" debounce="500" bottom-slots>
                                        <template v-slot:hint style='padding-top: 0;'><div>Nouveau mot de passe : au moins 8 charactères, avec des symboles/majuscule/chiffres.</div></template>
                                        <template v-slot:prepend><q-icon name="fas fa-lock"></q-icon></template>
                                        <template v-slot:append>
                                            <q-icon
                                                :name="displayPassword ? 'visibility_off' : 'visibility'"
                                                class="cursor-pointer"
                                                @click="displayPassword = !displayPassword"
                                            ></q-icon>
                                        </template>
                                        <template v-slot:label>
                                            &nbsp;<div id="passwdComplexity"></div>
                                        </template>
                                    </q-input>
                                </q-card-section>
                                <q-card-actions class="q-px-md">
                                    <q-btn type="submit" size="lg" class="full-width" :disabled="email == '' || !passwordComplexEnough" label="Enregistrer"></q-btn>
                                </q-card-actions>
                            </q-card>
                        </form>
                    </div>
                </div>
                </q-page-container>
            </q-layout>
        </template>
    </div>
</x-main-layout>