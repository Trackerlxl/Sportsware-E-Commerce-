// ============================================================
//  SPORTSWARE – slider.js
//  Lógica del slider de entrada (index.html)
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  const slides = document.querySelectorAll('.slide');
  const dots   = document.querySelectorAll('.dot');
  let index    = 0;
  let interval;

  if (!slides.length) return;

  function showSlide(i) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    slides[i].classList.add('active');
    if (dots[i]) dots[i].classList.add('active');
  }

  function nextSlide() {
    index = (index + 1) % slides.length;
    showSlide(index);
  }

  function startAutoSlide() {
    clearInterval(interval);
    interval = setInterval(nextSlide, 4000);
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      index = i;
      showSlide(index);
      startAutoSlide();
    });
  });

  let startX = 0;
  const slider = document.querySelector('.slider');
  if (slider) {
    slider.addEventListener('touchstart', e => { startX = e.touches[0].clientX; });
    slider.addEventListener('touchend', e => {
      const diff = startX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        index = diff > 0
          ? (index + 1) % slides.length
          : (index - 1 + slides.length) % slides.length;
        showSlide(index);
        startAutoSlide();
      }
    });
  }

  startAutoSlide();
});