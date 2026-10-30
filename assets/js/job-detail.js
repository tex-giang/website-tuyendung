/* Keep the selected vacancy consistent from list to detail to application. */
document.addEventListener('DOMContentLoaded', () => {
  const id = new URLSearchParams(location.search).get('id') || 'TG-01';
  const job = MOCK_JOBS.find(item => item.id === id);
  if (!job) { location.replace('404.html'); return; }
  const shortTitles = {
    'TG-01': 'Công nhân may công nghiệp', 'TG-02': 'Giám sát kiểm phẩm QC',
    'TG-03': 'Kỹ sư công nghiệp IE', 'TG-04': 'Quản lý đơn hàng',
    'TG-05': 'Bảo trì máy may', 'TG-06': 'Kế toán chi phí',
    'TG-07': 'Học nghề may có lương', 'TG-08': 'Lái xe đưa đón'
  };
  const intro = document.querySelector('main > section');
  intro.querySelector('h1').textContent = shortTitles[id];
  const breadcrumb = document.querySelector('main nav[aria-label="Breadcrumb"] > span:last-child');
  if (breadcrumb) breadcrumb.textContent = shortTitles[id];
  document.title = shortTitles[id] + ' | Tuyển dụng TEX-GIANG';
  intro.querySelector('.tg-chip').textContent = job.tags[0];
  const meta = intro.querySelector('.tg-chip + span');
  if (meta) meta.textContent = job.company + ' · ' + job.department + ' · Hạn nộp ' + job.deadline;
  intro.querySelector('p').textContent = job.description + ' (Tin minh hoạ giao diện, mã ' + job.id + '.)';
  const values = intro.querySelectorAll('dd');
  values[0].textContent = job.salary.replace(' VNĐ', ' đ');
  values[1].textContent = job.title.match(/^\d+/)?.[0] ? Number(job.title.match(/^\d+/)[0]) + ' người' : 'Xem thông tin tuyển dụng';
  values[2].textContent = ['TG-01', 'TG-07'].includes(id) ? 'Có đào tạo' : 'Trao đổi khi phỏng vấn';
  if (id !== 'TG-01') {
    const body = document.querySelector('main > section:nth-of-type(2) .space-y-12');
    const description = body.querySelector('ul');
    const line = document.createElement('li');
    line.textContent = job.description;
    description.replaceChildren(line);
    const benefits = body.children[1];
    benefits.querySelector('ul').innerHTML = '<li class="rounded-card bg-surface p-4"><strong class="block text-[16px] font-medium text-ink">Thu nhập theo vị trí</strong><span class="mt-1 block text-[16px] text-muted"></span></li><li class="rounded-card bg-surface p-4"><strong class="block text-[16px] font-medium text-ink">Trao đổi cùng nhân sự</strong><span class="mt-1 block text-[16px] text-muted">Xác nhận yêu cầu, chế độ và lịch làm việc khi phỏng vấn.</span></li>';
    benefits.querySelector('li span').textContent = job.salary;
    const address = body.children[2].querySelector('p');
    if (address) address.textContent = job.location;
  }
  const aside = document.querySelector('main aside');
  const panel = aside?.firstElementChild;
  if (panel) {
    panel.querySelectorAll('p')[1].textContent = job.salary.replace(' VNĐ', ' đ/tháng');
    panel.querySelectorAll('p')[3].textContent = job.deadline;
  }
  document.querySelectorAll('a[href^="apply.html?job="]').forEach(link => {
    link.href = 'apply.html?' + new URLSearchParams({ job: job.title, company: job.companyCode });
    if (job.status === 'closed') link.textContent = 'Nộp CV cho đợt sau';
  });
});
