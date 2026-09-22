<div class="crm-container handbook">
  {include file="CRM/Handbook/chrome.tpl"}
  <div class="handbook-layout">
    {include file="CRM/Handbook/toc.tpl"}
    <section class="handbook-results" aria-labelledby="handbook-results-heading">
      <h2 id="handbook-results-heading">{ts domain="handbook"}Suchergebnisse{/ts}</h2>
      {if $handbookTerm eq ''}
        <p>{ts domain="handbook"}Bitte einen Suchbegriff eingeben.{/ts}</p>
      {elseif $handbookResults}
        <p role="status">{ts domain="handbook" count=$handbookResults|@count plural="%count Treffer für „%1“" 1=$handbookTerm|escape}Ein Treffer für „%1“{/ts}</p>
        <ol>
          {foreach from=$handbookResults item=result}
            <li>
              <a href="{$result.url|escape}">{$result.title|escape}{if $result.heading} › {$result.heading|escape}{/if}</a>
              <p class="handbook-context-line">{$result.context|escape}</p>
            </li>
          {/foreach}
        </ol>
      {else}
        <p role="status">{ts domain="handbook" 1=$handbookTerm|escape}Keine Treffer für „%1“.{/ts}</p>
      {/if}
    </section>
  </div>
</div>
