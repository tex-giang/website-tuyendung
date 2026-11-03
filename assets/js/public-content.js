document.addEventListener('DOMContentLoaded',()=>{
 const count=TGJobs.all.filter(j=>TGJobs.status(j)==='published').length;
 document.querySelectorAll('[data-open-job-count]').forEach(el=>el.textContent=count);
 document.querySelectorAll('.job-card').forEach(card=>{
  const link=card.querySelector('h3 a');
  const id=new URL(link.href,location.href).searchParams.get('id');
  const job=TGJobs.all.find(j=>j.id===id);if(!job)return;
  link.textContent=job.shortTitle;
  const paragraphs=card.querySelectorAll('p');
  paragraphs[0].textContent=job.description;
  paragraphs[1].textContent=job.salary.replace(' VNĐ',' đ/tháng');
  paragraphs[2].textContent=job.location+' · Hạn nộp '+job.deadline;
  const cta=card.querySelector('a[href^="apply.html"]');if(cta){cta.href=TGJobs.applyURL(job);if(TGJobs.status(job)!=='published')cta.textContent='Hồ sơ dự phòng';}
 });
});
