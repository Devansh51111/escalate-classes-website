'use strict';
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{if(form.checkValidity()){const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Please wait…';}}}));
const toggle=document.getElementById('dark-toggle');if(toggle){toggle.addEventListener('click',()=>{document.body.classList.toggle('dark');localStorage.setItem('admin-dark',document.body.classList.contains('dark')?'1':'0');});}


if(toggle&&localStorage.getItem('admin-dark')==='1')document.body.classList.add('dark');
