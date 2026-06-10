document.querySelectorAll('.vehicle-card__image--preview').forEach(img => {
  const placeholder = img.previousElementSibling;
  const onLoad = () => { placeholder.hidden = true; };
  const onError = () => { img.remove(); };
  if (img.complete) {
    img.naturalWidth > 0 ? onLoad() : onError();
  } else {
    img.addEventListener('load', onLoad);
    img.addEventListener('error', onError);
  }
});
