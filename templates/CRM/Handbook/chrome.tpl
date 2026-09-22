{* Search form shown above every handbook screen. *}
<form class="handbook-search" role="search" method="get" action="{$handbookSearch.action|escape}">
  {foreach from=$handbookSearch.hidden key=name item=value}
    <input type="hidden" name="{$name|escape}" value="{$value|escape}">
  {/foreach}
  <label for="handbook-term">{ts domain="handbook"}Handbuch durchsuchen{/ts}</label>
  <input type="search" id="handbook-term" name="term" value="{$handbookTerm|escape}" class="crm-form-text">
  <button type="submit" class="crm-button btn btn-primary">{ts domain="handbook"}Suchen{/ts}</button>
</form>
