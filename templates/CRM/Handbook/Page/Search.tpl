<div class="crm-container handbook">
  {include file="CRM/Handbook/chrome.tpl"}
  <div class="handbook-layout">
    {include file="CRM/Handbook/toc.tpl"}
    <section class="handbook-results" aria-labelledby="handbook-results-heading">
      <h2 id="handbook-results-heading">{ts domain="handbook"}Suchergebnisse{/ts}</h2>
      {if $handbookTerm eq ''}
        <p>{ts domain="handbook"}Bitte einen Suchbegriff eingeben.{/ts}</p>
      {elseif $handbookResults}
        {capture assign=handbookSectionsText}{ts domain="handbook" count=$handbookSectionCount plural="%count Abschnitte"}Ein Abschnitt{/ts}{/capture}
        {capture assign=handbookPagesText}{ts domain="handbook" count=$handbookResults|@count plural="%count Seiten"}einer Seite{/ts}{/capture}
        <p role="status">{ts domain="handbook" 1=$handbookSectionsText 2=$handbookPagesText 3=$handbookTerm|escape}%1 auf %2 für „%3“{/ts}</p>
        <ol class="handbook-result-pages">
          {foreach from=$handbookResults item=result}
            <li>
              <h3><a href="{$result.url|escape}">{$result.title|escape}</a></h3>
              <p class="handbook-result-place">{$result.place|escape}</p>
              <ul class="handbook-hits">
                {foreach from=$result.hits item=hit}
                  <li>
                    <a href="{$hit.url|escape}">{if $hit.heading}{$hit.heading|escape}{else}{$result.title|escape}{/if}</a>
                    <p class="handbook-context-line">{$hit.context|escape}</p>
                  </li>
                {/foreach}
              </ul>
              {if $result.more}
                <p class="handbook-more">{ts domain="handbook" count=$result.more plural="und %count weitere Abschnitte"}und ein weiterer Abschnitt{/ts}</p>
              {/if}
            </li>
          {/foreach}
        </ol>
      {else}
        <p role="status">{ts domain="handbook" 1=$handbookTerm|escape}Keine Treffer für „%1“.{/ts}</p>
      {/if}
    </section>
  </div>
</div>
