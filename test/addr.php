<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>US Address Autocomplete</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #suggestions {
      position: absolute;
      z-index: 1000;
      width: 100%;
      max-height: 200px;
      overflow-y: auto;
    }
  </style>
</head>
<body class="p-4">

  <div class="mb-3">
    <label class="form-label">Start typing address</label>
    <input type="text" id="address" class="form-control" placeholder="e.g. 1600 Pennsylvania Ave">
    <div id="suggestions" class="list-group mt-1"></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Street Address</label>
    <input type="text" id="address_line1" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">City</label>
    <input type="text" id="city" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">State</label>
    <input type="text" id="state" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Zip Code</label>
    <input type="text" id="postcode" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Country</label>
    <input type="text" id="country" class="form-control">
  </div>

  <script>
    let timer;
    const debounceDelay = 300;

    document.getElementById('address').addEventListener('input', function () {
      clearTimeout(timer);
      const query = this.value.trim();
      if (query.length < 3) return;

      timer = setTimeout(() => {
        fetch('lookup.php?q=' + encodeURIComponent(query))
          .then(res => res.json())
          .then(data => {
            const suggestions = document.getElementById('suggestions');
            suggestions.innerHTML = '';
            data.forEach(place => {
              const div = document.createElement('div');
              div.className = 'list-group-item list-group-item-action';
              div.textContent = place.display_name;
              div.onclick = () => {
  document.getElementById('address').value = place.display_name;
  suggestions.innerHTML = '';

  const addr = place.address || {};

  // STREET
  let street = '';
  if (addr.house_number && addr.road) {
    street = addr.house_number + ' ' + addr.road;
  } else if (addr.road) {
    street = addr.road;
  } else if (addr.street) {
    street = addr.street;
  }

  // CITY
  let city = addr.city || addr.town || addr.village || addr.hamlet || '';

  // STATE
  let state = addr.state || addr.region || addr.county || '';

  // ZIP
  let postcode = addr.postcode || '';

  // COUNTRY
  let country = addr.country || '';

  document.getElementById('address_line1').value = street;
  document.getElementById('city').value = city;
  document.getElementById('state').value = state;
  document.getElementById('postcode').value = postcode;
  document.getElementById('country').value = country;
};

              suggestions.appendChild(div);
            });
          });
      }, debounceDelay);
    });
  </script>

</body>
</html>
