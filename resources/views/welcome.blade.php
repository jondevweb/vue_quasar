<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">

    <title>ICI On Recycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('image/Favicon_IOR.svg') }}">
    <link rel="apple-touch-icon" href="{{ url('image/Favicon_IOR.png') }}">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <!-- https://www.srihash.org/ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/minireset.css/0.0.2/minireset.min.css" integrity="sha512-uBLaY+6crwV4JAHILx0HWvYncrX7TXL770hqxly0ZsQ199v4lr2yNB2jiPMoxNajFPHSQnU80B1O8dJLujWZMg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900|Material+Icons" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/5.9.55/css/materialdesignicons.min.css" integrity="sha512-vIgFb4o1CL8iMGoIF7cYiEVFrel13k/BkTGvs0hGfVnlbV6XjAA0M0oEHdWqGdAVRTDID3vIZPOHmKdrMAUChA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quasar@2.1.0/dist/quasar.prod.css" integrity="sha512-F2yTNwkcXbskhcrn6suOBxhLttKqn7LWhZRk1VOAcgdSGPQtT0Bs+x3Cxd+T1e48hi9hWttSZYGoacDTRfcC+A==" crossorigin="anonymous">

    </head>

  <body>
    <script>
    quasarConfig = {
        config: {
        brand: {
            primary: '#027BE3',
            secondary: '#26A69A',
            accent: '#9C27B0',

            dark: '#1d1d1d',

            positive: '#21BA45',
            negative: '#C10015',
            info: '#31CCEC',
            warning: '#F2C037'
        }
        }
    }
    </script>
    <style>
    .lien_portail {
        font-weight: bold;
        font-style: inherit;
        text-decoration: unset;
        border-style: solid;
        border-color: #003f6e;
        border-radius: 0.6em;
        padding: 0.3em;
        color: #003f6e !important;
        font-size: 1em;
        border-width: 2px;
        text-transform: none;
    }
    .lien_collecteurs {
        color: black;
        font-size: 0.9em;
    }
    .logowrapper {
        min-width: 300px;
        width: 300px;
        text-align: center;
    }
    .boite_a_feuille {
        background-image: url(/image/feuille.svg);
        background-position: center;
        background-repeat: no-repeat;
        background-size: contain;
        padding-top: 2em;
        padding-bottom: 2em;
        margin-bottom: 2em;
        margin-top: 2em;
    }
    .boite_a_feuille * {
        color: #003f6e;
    }
    .corps {
        padding-top: 56px;
        margin-left: 5vw;
        margin-right: 5vw;
    }
    .logo {
        height: 4em;
    }
    .logo-section {
        text-align: center;
        height: 10em;
    }
    .logo-triethic {
        height: 7em;
    }
    @media (max-width: 480px) {
        .logo-triethic {
            height: 5em;
        }
        .logo-moble {
            height: 2.5em;
        }
    }

    </style>

    <div id='q-app' class="q-pa-md" style='padding: 0;'>
        <template v-if='true'>
        <q-layout view="hHh lpR fFf">
            <q-header elevated style='height: 4em;background-color: white;'>
                <q-toolbar class='fit row wrap justify-start items-start content-center' style='padding-left: 0;'>
                    <div class='logowrapper'>
                        <q-img
                            src="/image/ID-icionrecycle-logo.svg"
                            spinner-color="white"
                            style="weight: 140px; max-width: 150px"
                            mode='fit'
                        ></q-img>
                    </div>
                    <q-space></q-space>
                    <div class="row items-center no-wrap">
                        <a href="/clients"     alt="Accès au portail pour les clients"><q-btn color="white" text-color="black" label="Connexion clients" class='lien_portail'></q-btn></a>
                    </div>
                </q-toolbar>
            </q-header>
            <q-page-container class='corps'>
                <div class='boite_a_feuille'>
                    <div class="row">
                        <div class="col-12 text-h4 text-weight-bold text-center">
                            Bienvenue sur notre plateforme de traçabilité !
                        </div>
                    </div>
                    <div class="row justify-center">
                        <div class="text-h4 text-weight-bold text-center" style='width: 1.5em;background-color: rgb(177, 220, 76);height: 0.3em;margin-top: 1em;margin-bottom: 1em;'>
                        &nbsp;
                        </div>
                    </div>
                    <div class="row" style='margin-bottom: 3em;'>
                        <div class="col-12 text-h5 text-center">
                            Nos autres solutions durables et innovantes
                        </div>
                    </div>
                </div>
                <div class="row justify-center">
                    <div style='width: 100%' class="q-ma-xs">
                        <a href='https://triethic.fr'>
                            <q-card class="my-card shadow-5">
                                <q-card-section class='logo-section items-center row'>
                                    <q-img src="/image/welcome/ID-triethic-logo-principal-2coul.svg" class='logo-triethic' class='logo' fit='contain' spinner-color="white"></q-img>
                                </q-card-section>
                            </q-card>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md q-ma-xs">
                        <a href='https://triethic.fr/solutions-durables-et-innovation/vimethic/'>
                            <q-card class="my-card shadow-5">
                                <q-card-section class='logo-section items-center row'>
                                    <q-img src="/image/welcome/ID-vimethic-logo-full.svg" class='logo' fit='contain' spinner-color="white"></q-img>
                                </q-card-section>
                            </q-card>
                        </a>
                    </div>
                    <div class="col-12 col-md q-ma-xs">
                        <a href='https://triethic.fr/solutions-durables-et-innovation/moble/'>
                            <q-card class="my-card shadow-5">
                                <q-card-section class='logo-section items-center row'>
                                    <q-img src="/image/welcome/ID-moble-logo-full.svg" class='logo logo-moble' fit='contain' spinner-color="white"></q-img>
                                </q-card-section>
                            </q-card>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md  q-ma-xs">
                        <a href='https://triethic.fr/solutions-durables-et-innovation/casquethic/'>
                            <q-card class="my-card shadow-5">
                                <q-card-section class='logo-section items-center row'>
                                    <q-img src="/image/welcome/ID-casquetic-logo-full.svg" class='logo' fit='contain' spinner-color="white"></q-img>
                                </q-card-section>
                            </q-card>
                        </a>
                    </div>
                    <div class="col-12 col-md  q-ma-xs">
                        <a href='https://triethic.fr/solutions-durables-et-innovation/masquethic/'>
                            <q-card class="my-card shadow-5">
                                <q-card-section class='logo-section items-center row'>
                                    <q-img src="/image/welcome/masquethic-logo-full.svg" class='logo' fit='contain' spinner-color="white"></q-img>
                                </q-card-section>
                            </q-card>
                        </a>
                    </div>
                </div>
            </q-page-container>
            <div class='column' style='margin-top: 2em;'>
                <div class='col'>
                    <div class="row justify-center" style='background-color: #003f6e;color: white;padding-top: 2em;padding-bottom: 2em;line-height: 0;'>
                        <div class='column wrap justify-start items-start content-start' stylee='text-align: right;margin-bottom: 1em;'>
                            <q-img
                                src="/image/ID-triethic-logo-principal-white.svg"
                                spinner-color="white"
                                style="width: 10em;"
                                fit='contain'
                            ></q-img>
                            <q-img
                                src="/image/macaron EA.svg"
                                spinner-color="white"
                                style="height: 5em;margin-top: 1em;"
                                fit='contain'
                            ></q-img>
                        </div>
                        <div class="column wrap justify-center items-start content-start">
                            <div style='text-align: center;font-size: 0.75em;'>
                                <p>320 avenue de la république</p>
                                <p>92000 Nanterre</p>
                                <p>+33 (0)9 80 77 40 59</p>
                                <p>Nous contacter par mail</p>
                                <p>&nbsp;</p>
                                <p>Horaires d’ouverture :</p>
                                <p>du lundi au vendredi, de 9h à 18h.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='col' style='text-align: center;background-color: #003556;color: white;font-size: 0.8em;padding-top: 2em;padding-bottom: 2em;'>
                <a href='/data/crédits et mentions légales.pdf' style='color: white'>Crédits Mentions légales 2021</a>. Tous droits réservés. <a href='javascript:void(0)' style='color: white' @click='cookie'>Notre politique de cookies.</a>
                </div>
            </div>
        </q-layout>
        </template>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"  integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.12/vue.global.min.js" integrity="sha512-CLrLddI89KM+4mefYP8PbruyeARQAoQVwajhryM9hwM5bAeEFdtBwk0Wy6A9HlVeXyszFtXqOzg1Qy/A+Fwglg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.12/vue.global.prod.min.js" integrity="sha512-tk9eITbdhiiOuxe3Q3Qiy70ern7eyMEKzJdHiw9bdrVUPySg9YrLVRcx6USiaxiANKbGnI15HTnIJYLhvvSMjQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>-->
    <script src="https://cdn.jsdelivr.net/npm/quasar@2.1.0/dist/quasar.umd.prod.min.js" integrity="sha512-EE4RJ0NtJr8vfkjndUlvMAfSgFF+5GzLB4IzaNJ5rKQXYthdODzTodOja31x1QZockQfSihXd7pyG4Bqrj1k7Q==" crossorigin="anonymous"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/quasar@2.1.0/dist/quasar.umd.prod.min.js" integrity="sha512-aWFLBPY/2/0yuQrF+TIwUqYfn2iutcW/xWha8M9uY4Y+YEnB7Qs4UYfWb87W3QoLvtzAKdiVmebZIOE/hqMzLw==" crossorigin="anonymous"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/quasar@2.1.0/dist/lang/fr.umd.prod.js" integrity="sha512-2pII037qHj9opU0WBY2ZEkI63wrYiaKErFtKkoXkYn5GnWNOMX+LtGpbmM7mvFC2f43riXONKOmhvGBndjqWOw==" crossorigin="anonymous"></script>

    <script>
        const QApp = {
            el: '#q-app',
            data: function () {
                return {
                }
            },
            methods: {
                go: function(url) {
                    window.location = url
                },
                cookie: function() {
                    Quasar.Notify.create({
                        icon: 'thumb_up',
                        message: "Nous tenons à la vie privée de nos utilisateurs.<br>Pour cela, nous avons mis des dispositifs en place afin de ne collecter aucune donnée personnelle.<br>Le chat live a été conçu par nous-mêmes et nous avons fait en sorte de ne récolter aucune données de votre part lorsque vous utilisez ce service.<br>Nous souhaitons apporter la navigation la plus sécurisée possible à tous nos internautes.",
                        position: 'bottom',
                        multiLine: true,
                        html: true,
                        actions:[
                            { label: 'Fermer' }
                        ],
                        timeout: 0
                    })
                },
            },
            mounted: function() {
            }
        }
        const app = Vue.createApp(QApp)
        app.use(Quasar, quasarConfig)
        Quasar.lang.set(Quasar.lang.fr)
        app.mount('#q-app')
        </script>
  </body>
</html>
