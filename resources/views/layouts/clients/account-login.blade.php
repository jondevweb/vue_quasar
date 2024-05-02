<x-common.account-login-layout target="clients" :path='$path' :logged-but-no-permission='$loggedButNoPermission ?? "false"'>
    <script>

    </script>
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
    body {
        background-color: #e0e0e0;
    }

    body {
        background-color: black;
        background-image: url("/image/login-background.jpg");
        height: 90%;
        background-size: 100% auto;
    }
    .q-field--with-bottom {
        background-color: #FFFFFF99;
        border-radius: 1em;
        padding-bottom: 2em;
        margin-bottom: 1em;
        padding-left: 2em;
        padding-right: 2em;
    }
    </style>
    <div id='q-app'>
        <template v-if='true'>
            <q-layout view="hHh lpR fFf">
                <q-page-container class="window-height window-width row justify-center items-center">
                <div class="column">
                    <div class="row">
                    </div>
                    <div class="row">
                        <q-form @submit="onSubmit">
                            <q-card flat squared classe="q-pa-lg" style='background: transparent;'>
                                <q-card-section style='margin-bottom: 0;padding-bottom: 0;'>
                                    <q-img
                                        fit='contain'
                                        src="/image/ID-icionrecycle-logo.svg"
                                        spinner-color="white"
                                    ></q-img>
                                </q-card-section>
                                <q-card-section style='margin: 1rem;'>
                                    <q-input v-model="email" type="email" label="" bottom-slots hint='Courriel' :rules="[ val => emailValidator(val) || 'Courriel invalide']">
                                        <template v-slot:prepend><q-icon name="fas fa-envelope"></q-icon></template>
                                    </q-input>
                                    <q-input v-model="password" :type="isPwd ? 'password' : 'text'" label="" hint='Mot de passe' :rules='[ val => val.length > 0 || ""  ]' bottom-slots>
                                        <template v-slot:prepend><q-icon name="fas fa-lock"></q-icon></template>
                                        <template v-slot:append>
                                            <q-icon
                                                :name="isPwd ? 'visibility_off' : 'visibility'"
                                                class="cursor-pointer"
                                                @click="isPwd = !isPwd"
                                            ></q-icon>
                                            </template>
                                    </q-input>
                                </q-card-section>
                                <q-card-actions class="q-px-md"  style='margin: 1rem;margin-bottom: 0'>
                                    <q-btn type="submit" size="lg" class="full-width" :disabled="!emailValidity || password.length == 0" label="Se connecter" style='width: 17em !important;opacity: 1 !important;background: #003f6e;color: white;'></q-btn>
                                </q-card-actions>
                                <q-card-section style='padding:0;padding: 0;text-align: center;height: 1em;'>
                                    <a href='javascript:void(0)' v-on:click='askConfirm' v-show='emailValidity' style='font-size: x-small;color: white;'>Vous avez oublié votre mot de passe ?</a>
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