<script>
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;

</script>

<SideNav activeMenu="pos" />
<Header {y} {innerHeight} />