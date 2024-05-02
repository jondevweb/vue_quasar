<template>
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
                                style="max-width: 150px"
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
                            <!-- <template v-slot:no-option>
                            <q-item>
                                <q-item-section class="text-grey">
                                Aucun site de trouvé
                                </q-item-section>
                            </q-item>
                            </template> -->
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
                                <!-- <template v-slot:label>
                                    <div class="row items-center no-wrap">
                                    <div class="text-center" style='font-weight: normal;font-size: smaller;line-height: normal;'>
                                        @{{account.model.prenom}}<br>@{{account.model.nom}}
                                    </div>
                                    </div>
                                </template> -->
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
                            <!-- <template v-slot='props'>
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
                            </template>-->
                        </ghost-wrapper>
                    </q-dialog>
                    <q-dialog v-model="parametres.dialog" persistent>
                        <ghost-wrapper v-model:one='parametres.tree'>
                            <!-- <template v-slot='props'>
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
                            </template> -->
                        </ghost-wrapper>
                    </q-dialog>
                    <q-dialog v-model="headBar.demandeCollecte.dialog" persistent>
                        <ghost-wrapper v-model:one='parametres.tree'>
                        </ghost-wrapper>
                    </q-dialog>
                    <div id='vue-body'></div>
                </q-page-container>
            </q-layout>
</template>

<script setup>
defineOptions({
  name: 'ClientWelcome'
});
</script>
