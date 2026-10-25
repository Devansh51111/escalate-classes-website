'use strict';
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{if(form.checkValidity()){const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Please wait…';}}}));
const toggle=document.getElementById('dark-toggle');if(toggle){toggle.addEventListener('click',()=>document.body.classList.toggle('dark'));}
