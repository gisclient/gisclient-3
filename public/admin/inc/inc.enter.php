<TABLE cellSpacing="0" cellPadding="0" width="100%" border="0">
  <TBODY>
  <TR>
	<td class="testo_login">
		
		Accesso consentito agli utenti autorizzati.
		
	</td>
	<TD colspan="3">
		<form class="riquadro" id="frm_enter" onsubmit="return false;">
		  <table width="100%" border="0" align="center" cellpadding="4" cellspacing="0">
		      <td width="22%" class="label">Utente:</td>
			  <td width="78%"><input name="username" type="text" id="username" tabindex=1></td>
		    </tr>
		    <tr>
		      <td class="label">Password:</td> 
		      <td>
				<input name="password" type="password" id="password" tabindex=2>
				<input name="enc_password" type="hidden" id="enc_password">
			  </td>
		    </tr>
		    <tr>
				<td></td>
		      <td align="right">
				<input type="submit" name="azione" value="Entra" style="width:80" tabindex="3" onclick="javascript:accedi();">
			  </td>
		    </tr>
		  </table>
		</form>
	</TD>
  </TR>
  <TR><TD style="text-align:right; background-color:#728bb8; border-bottom:6px solid #415578; padding:6 6 1 6px" colspan="5">&nbsp;<!--<a href="http://www.gisweb.it" target="_blank"><img src="./images/logoblu.png" border="0"></a>--></td></TR>
  </TBODY> 
   <script>xGetElementById('username').focus();</script>
</TABLE>