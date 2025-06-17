// signup-override.js - clean JavaScript for plan/card handling

document.addEventListener('DOMContentLoaded', function () {
  const planGrid = document.getElementById('planGrid');
  const hiddenPlan = document.getElementById('hiddenPlan');
  const hiddenAccountType = document.getElementById('hiddenAccountType');
  const continueBtn = document.getElementById('continueBtn');

  const addCheckmark = (wrapper) => {
    document.querySelectorAll('.plan-checkmark-badge').forEach(el => el.remove());

    const badge = document.createElement('div');
    badge.className = 'plan-checkmark-badge';
    badge.innerHTML = '✓';
    wrapper.appendChild(badge);
  };

  const applyPreselected = () => {
    const preselectedWrapper = document.querySelector('.plan-card-wrapper.selected');
    if (!preselectedWrapper) return;

    addCheckmark(preselectedWrapper);
    const card = preselectedWrapper.querySelector('.plan-card');
    hiddenPlan.value = card.dataset.planId;
    hiddenAccountType.value = document.querySelector('.account-type-btn.active')?.dataset.accountType || 'user';
    continueBtn.disabled = false;
    continueBtn.textContent = 'Continue';
  };

  if (planGrid) {
    planGrid.addEventListener('click', function (e) {
      const card = e.target.closest('.plan-card');
      if (!card) return;

      const wrapper = card.closest('.plan-card-wrapper');

      document.querySelectorAll('.plan-card-wrapper').forEach(w => w.classList.remove('selected'));
      document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));

      wrapper.classList.add('selected');
      card.classList.add('selected');

      addCheckmark(wrapper);
      hiddenPlan.value = card.dataset.planId;
      hiddenAccountType.value = document.querySelector('.account-type-btn.active')?.dataset.accountType || 'user';
      continueBtn.disabled = false;
      continueBtn.textContent = 'Continue to Account Details';
    });
  }

  applyPreselected();
});
