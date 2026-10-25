'use strict';
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{if(form.checkValidity()){const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Please wait…';}}}));
const toggle=document.getElementById('dark-toggle');if(toggle){toggle.addEventListener('click',()=>{document.body.classList.toggle('dark');localStorage.setItem('admin-dark',document.body.classList.contains('dark')?'1':'0');});}

document.querySelectorAll('[data-review-direction]').forEach(button=>button.addEventListener('click',()=>{const track=document.getElementById('review-track');track?.scrollBy({left:Number(button.dataset.reviewDirection)*320,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}));

if(toggle&&localStorage.getItem('admin-dark')==='1')document.body.classList.add('dark');
