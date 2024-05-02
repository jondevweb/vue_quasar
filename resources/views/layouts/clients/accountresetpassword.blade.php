<x-main-layout>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js" integrity="sha512-TZlMGFY9xKj38t/5m2FzJ+RM/aD5alMHDe26p0mYUMoCF5G7ibfHUQILq0qQPV3wlsnCwL+TPRNK4vIWGLOkUQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.1.0/progressbar.min.js" integrity="sha512-EZhmSl/hiKyEHklogkakFnSYa5mWsLmTC4ZfvVzhqYNLPbXKAXsjUYRf2O9OlzQN33H0xBVfGSEIUeqt9astHQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <x-slot name="script">
        <script>
        var token = '{{$token}}'
        var fr = {
            "code": "fr",
            "messages": {
                "alpha": "Le champ {_field_} ne peut contenir que des lettres",
                "alpha_num": "Le champ {_field_} ne peut contenir que des caractères alpha-numériques",
                "alpha_dash": "Le champ {_field_} ne peut contenir que des caractères alpha-numériques, tirets ou soulignés",
                "alpha_spaces": "Le champ {_field_} ne peut contenir que des lettres ou des espaces",
                "between": "Le champ {_field_} doit être compris entre {min} et {max}",
                "confirmed": "Le champ {_field_} ne correspond pas",
                "digits": "Le champ {_field_} doit être un nombre entier de {length} chiffres",
                "dimensions": "Le champ {_field_} doit avoir une taille de {width} pixels par {height} pixels",
                "email": "Cela doit être une adresse courriel valide",
                "excluded": "Le champ {_field_} doit être une valeur valide",
                "ext": "Le champ {_field_} doit être un fichier valide",
                "image": "Le champ {_field_} doit être une image",
                "integer": "Le champ {_field_} doit être un entier",
                "length": "Le champ {_field_} doit contenir {length} caractères",
                "max_value": "Le champ {_field_} doit avoir une valeur de {max} ou moins",
                "max": "Le champ {_field_} ne peut pas contenir plus de {length} caractères",
                "mimes": "Le champ {_field_} doit avoir un type MIME valide",
                "min_value": "Le champ {_field_} doit avoir une valeur de {min} ou plus",
                "min": "Le champ {_field_} doit contenir au minimum {length} caractères",
                "numeric": "Le champ {_field_} ne peut contenir que des chiffres",
                "oneOf": "Le champ {_field_} doit être une valeur valide",
                "regex": "Le champ {_field_} est invalide",
                "required": "Le champ {_field_} est obligatoire",
                "required_if": "Le champ {_field_} est obligatoire lorsque {target} possède cette valeur",
                "size": "Le champ {_field_} doit avoir un poids inférieur à {size}KB",
                "double": "Le champ {_field_} doit être une décimale valide"
            }
        }
        var bar;
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
        new Vue({
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
                onSubmit: function() {
                    var vm = this
                    post('/api/v1.0/clients/recup', {email: this.email, password: this.email, password_confirmation: this.email, token: token}, function(data) {
                        if (data.status) {
                            vm.$q.dialog({
                                title: 'Mot de passe changé',
                                message: "Vous allez maintenant être redirigé vers la page d'accueil pour vous connecter.",
                                persistent: true,
                                html: true
                            }).onOk(() => {location = location.protocol+'//'+location.host+'/clients'})
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
        })
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
        <template>
            <q-layout view="hHh lpR fFf">
                <q-page-container class="window-height window-width row justify-center items-center">
                <div class="column">
                    <div class="row">
                        <h5 class="text-h5 q-my-md">ICI On Recycle</h5>
                    </div>
                    <div class="row">
                        <validation-observer v-slot="{ invalid, handleSubmit}">
                            <form @submit.prevent="handleSubmit(onSubmit)">
                                <q-card square bordered class="q-pa-lg shadow-1" style='min-width: 20rem;'>
                                    <q-card-section>
                                            <validation-provider name='courriel' rules="email" v-slot="{ errors }">
                                                <q-input v-model="email" type="email" label=""
                                                        bottom-slots hint='Courriel'
                                                        v-bind:error-message="errors[0]" :error="errors.length > 0">
                                                    <template v-slot:prepend><q-icon name="fas fa-envelope"></q-icon></template>
                                                </q-input>
                                            </validation-provider>
                                            <validation-provider name='mot de passe' rules="min:1" v-slot="{ errors }">
                                                <q-input v-model="password" :type="displayPassword ? 'text' : 'password'" label=""
                                                     debounce="500"
                                                     bottom-slots>
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
                                            </validation-provider>
                                    </q-card-section>
                                    <q-card-actions class="q-px-md">
                                        <q-btn type="submit" size="lg" class="full-width" :disabled="invalid || password == '' || email == '' || !passwordComplexEnough" label="Enregistrer"></q-btn>
                                    </q-card-actions>
                                </q-card>
                            </form>
                        </validation-observer>
                    </div>
                </div>
                </q-page-container>
            </q-layout>
        </template>
    </div>
</x-main-layout>