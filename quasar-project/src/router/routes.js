import InformationsGenerales from '../pages/InformationsGenerales.vue'
// import ContactsAssocies from '../components/ContactsAssocies.vue'
import ContactsAssocies from '../pages/ContactsAssocie.vue'
import ConditionsDAcces from '../components/ConditionsDAcces.vue'
import IndexPage from '../pages/IndexPage.vue'

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', name: 'home', components: { default: IndexPage, content: IndexPage}},
      { path: '', name: 'informationsGenerales', components: { default: InformationsGenerales, content: InformationsGenerales}},
      { path: '', name: 'associes', components: { default: ContactsAssocies, content: ContactsAssocies}},
      { path: '', name: 'acces', components: { default: ConditionsDAcces, content: ConditionsDAcces}},
    ]
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
