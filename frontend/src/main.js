import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import App from './App.vue'
import router from './router' // Connects your routing
import './style.css' // Your global styles

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)   

app.use(router)
app.use(Toast, {
  position: 'top-right',
  timeout: 4000,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
  maxToasts: 5,
  newestOnTop: true,
})

const options = {
  toastClassName: "my-custom-toast-class",
  bodyClassName: "my-custom-toast-body",
  containerClassName: "my-toast-container"
};

app.use(Toast, options);
app.mount('#app')