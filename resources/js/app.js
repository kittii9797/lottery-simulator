/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';
import 'bootstrap/dist/css/bootstrap.min.css';

const app = createApp({});

import LottoComponent from './components/LottoComponent.vue';
app.component('lotto-component', LottoComponent);

import NavbarComponent from './components/Header.vue';
app.component('navbar-component', NavbarComponent);


app.mount('#app');
