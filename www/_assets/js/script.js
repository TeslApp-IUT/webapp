document.addEventListener('DOMContentLoaded', function () {
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach((item) => {
    const question = item.querySelector('.faq-question');
    question.addEventListener('click', function () {
      const isActive = item.classList.contains('active');
      faqItems.forEach((otherItem) => {
        if (otherItem !== item) {
          otherItem.classList.remove('active');
          const otherQuestion = otherItem.querySelector('.faq-question');
          if (otherQuestion) {
            otherQuestion.setAttribute('aria-expanded', 'false');
          }
        }
      });
      item.classList.toggle('active', !isActive);
      question.setAttribute('aria-expanded', String(!isActive));
    });
    question.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        question.click();
      }
    });
  });
});
