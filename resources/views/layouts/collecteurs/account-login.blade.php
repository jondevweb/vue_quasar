<x-common.account-login-layout target="collecteurs" :path='$path' :logged-but-no-permission='"false"'>
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
    </style>


    <div id='q-app'>
        <template v-if='true'>
            <q-layout view="hHh lpR fFf">
                <q-page-container class="window-height window-width row justify-center items-center">
                <div class="column">
                    <div class="row">
                        <h5 class="text-h5 q-my-md">ICI On Recycle - Accès collecteurs</h5>
                    </div>
                    <div class="row">
                        <q-form @submit="onSubmit">
                            <q-card square bordered class="q-pa-lg shadow-1" style='min-width: 20rem;'>
                                <q-card-section>
                                    <q-input v-model="email" type="email" label="" bottom-slots hint='Courriel' :rules="[ val => emailValidator(val)]">
                                        <template v-slot:prepend><q-icon name="fas fa-envelope"></q-icon></template>
                                    </q-input>
                                    <q-input v-model="password" type="password" label="" hint='Mot de passe' :rules='[ val => val.length > 0 || ""  ]' bottom-slots>
                                        <template v-slot:prepend><q-icon name="fas fa-lock"></q-icon></template>
                                    </q-input>
                                </q-card-section>
                                <q-card-actions class="q-px-md">
                                    <q-btn type="submit" size="lg" class="full-width" :disabled="!emailValidity || password.length == 0" label="Connexion"></q-btn>
                                </q-card-actions>
                                <q-card-section style='height: 1rem'>
                                    <a href='javascript:void(0)' v-on:click='askConfirm' v-show='emailValidity'>Mot de passe oublié ?</a>
                                </q-card-section>
                            </q-card>
                        </q-form>
                    </div>
                </div>
                </q-page-container>
            </q-layout>
        </template>
    </div>
</x-common.account-login-layout>