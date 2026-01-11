//+------------------------------------------------------------------+
//|                  Position Sync EA v10 - CLOSED ONLY              |
//+------------------------------------------------------------------+
#property strict

input string   API_URL      = "https://jawi.asia/exness_a/history.php";
// Token hardcoded untuk keamanan (TIDAK dijadikan input parameter)
const string   API_TOKEN    = "abc321Xyz";  // Token untuk authentication - HARDCODED
input int      SYNC_INTERVAL = 2
input bool     ENABLE_LOGS   = true;
input int      HISTORY_DAYS  = 30;  // Selalu sync X hari terakhir setiap kali
input bool     FULL_SYNC_ALWAYS = true;  // TRUE = sync semua history setiap kali

datetime lastSyncTime;

int OnInit()
{
   EventSetTimer(1);
   lastSyncTime = TimeCurrent();
   
   if(!SyncPositions(true))
   {
      Alert("Gagal sync awal v10! Cek log.");
      return INIT_FAILED;
   }
   if (ENABLE_LOGS) Print("=== EA v10 CLOSED ONLY Started ===");
   if (ENABLE_LOGS) Print("Mode: ", FULL_SYNC_ALWAYS ? "FULL SYNC" : "INCREMENTAL");
   if (ENABLE_LOGS) Print("History Days: ", HISTORY_DAYS);
   if (ENABLE_LOGS) Print("Next sync in ", SYNC_INTERVAL, " seconds");
   return INIT_SUCCEEDED;
}

void OnDeinit(const int reason)
{
   EventKillTimer();
   if (ENABLE_LOGS) Print("=== EA v10 CLOSED ONLY Stopped ===");
}

void OnTimer()
{
   static datetime lastCheck;
   datetime now = TimeCurrent();
   if (now - lastCheck >= 1)
   {
      if (now - lastSyncTime >= SYNC_INTERVAL)
      {
         SyncPositions(false);
      }
      lastCheck = now;
   }
}

bool SyncPositions(bool isInitialSync)
{
   datetime now = TimeCurrent();
   
   // Kalau FULL_SYNC_ALWAYS = true, selalu ambil semua data dari HISTORY_DAYS terakhir
   // Kalau false, hanya ambil data baru sejak lastSyncTime
   datetime fromTime;
   if(FULL_SYNC_ALWAYS || isInitialSync)
   {
      fromTime = now - (HISTORY_DAYS * 86400);
      if(ENABLE_LOGS) Print(">>> FULL SYNC: Loading ", HISTORY_DAYS, " days history");
   }
   else
   {
      fromTime = lastSyncTime;
      if(ENABLE_LOGS) Print(">>> INCREMENTAL SYNC: From last sync");
   }
   
   datetime toTime = now;
   
   string jsonData = "{\"account\":\"" + IntegerToString(AccountInfoInteger(ACCOUNT_LOGIN)) + "\",\"token\":\"" + API_TOKEN + "\",\"positions\":[";
   bool first_item = true;
   int closed_count = 0;
   
   // ========== SKIP OPEN POSITIONS - HANYA SYNC CLOSED ==========
   
   // ========== SYNC CLOSED POSITIONS ==========
   if(HistorySelect(fromTime, toTime))
   {
      int total_deals = HistoryDealsTotal();
      if(ENABLE_LOGS) Print("Found ", total_deals, " deals in history");
      
      // Array untuk track position_id yang sudah diproses
      ulong processed_positions[];
      int processed_count = 0;
      
      for(int i = 0; i < total_deals; i++)
      {
         ulong deal_ticket = HistoryDealGetTicket(i);
         if(deal_ticket == 0) continue;
         
         // Hanya ambil deal dengan DEAL_ENTRY_OUT (posisi closing)
         if(HistoryDealGetInteger(deal_ticket, DEAL_ENTRY) != DEAL_ENTRY_OUT) continue;
         
         ulong position_id = HistoryDealGetInteger(deal_ticket, DEAL_POSITION_ID);
         if(position_id == 0) continue;
         
         // Skip jika position_id sudah diproses
         bool already_processed = false;
         for(int j = 0; j < processed_count; j++)
         {
            if(processed_positions[j] == position_id)
            {
               already_processed = true;
               break;
            }
         }
         if(already_processed) continue;
         
         // Tambahkan ke array processed
         ArrayResize(processed_positions, processed_count + 1);
         processed_positions[processed_count] = position_id;
         processed_count++;
         
         if(!first_item) jsonData += ",";
         jsonData += BuildClosedPositionJSON(position_id, fromTime, toTime);
         first_item = false;
         closed_count++;
      }
   }
   
   jsonData += "]}";
   
   if(ENABLE_LOGS) 
   {
      Print("Syncing: ", closed_count, " closed positions ONLY");
   }
   
   if (SendToAPI(jsonData))
   {
      lastSyncTime = toTime;
      if (ENABLE_LOGS) Print("✓ Sync SUCCESS at ", TimeToString(toTime));
      return true;
   }
   
   if (ENABLE_LOGS) Print("✗ Sync FAILED");
   return false;
}

string EscapeJSON(string str)
{
   string result = "";
   int len = StringLen(str);
   for(int i = 0; i < len; i++)
   {
      ushort ch = StringGetCharacter(str, i);
      if(ch == '"')
         result += "\\\"";
      else if(ch == '\\')
         result += "\\\\";
      else if(ch == '\n')
         result += "\\n";
      else if(ch == '\r')
         result += "\\r";
      else if(ch == '\t')
         result += "\\t";
      else
         result += ShortToString(ch);
   }
   return result;
}

string BuildClosedPositionJSON(ulong position_id, datetime saved_from, datetime saved_to)
{
   string json = "{";
   
   // Variables untuk data position
   string symbol = "";
   string position_type = "";
   double volume = 0.0;
   double entry_price = 0.0;
   double close_price = 0.0;
   datetime entry_time = 0;
   datetime close_time = 0;
   string comment = "";
   
   // Variables untuk profit calculation
   double total_profit = 0.0;
   double total_swap = 0.0;
   double total_commission = 0.0;
   
   // Backup current history selection
   datetime backup_from = saved_from;
   datetime backup_to = saved_to;
   
   // Select history by position ID untuk akurasi profit
   if(HistorySelectByPosition(position_id))
   {
      int total_deals = HistoryDealsTotal();
      
      for(int j = 0; j < total_deals; j++)
      {
         ulong deal_ticket = HistoryDealGetTicket(j);
         if(deal_ticket == 0) continue;
         
         long deal_entry = HistoryDealGetInteger(deal_ticket, DEAL_ENTRY);
         
         // Deal entry (opening position)
         if(deal_entry == DEAL_ENTRY_IN)
         {
            symbol = HistoryDealGetString(deal_ticket, DEAL_SYMBOL);
            long deal_type = HistoryDealGetInteger(deal_ticket, DEAL_TYPE);
            position_type = GetDealType(deal_type);
            volume += HistoryDealGetDouble(deal_ticket, DEAL_VOLUME); // Accumulate untuk partial close
            entry_price = HistoryDealGetDouble(deal_ticket, DEAL_PRICE);
            entry_time = (datetime)HistoryDealGetInteger(deal_ticket, DEAL_TIME);
            
            // Ambil comment dari order
            ulong order_ticket = HistoryDealGetInteger(deal_ticket, DEAL_ORDER);
            if(order_ticket > 0 && HistoryOrderSelect(order_ticket))
            {
               comment = HistoryOrderGetString(order_ticket, ORDER_COMMENT);
            }
            if(StringLen(comment) == 0)
            {
               comment = HistoryDealGetString(deal_ticket, DEAL_COMMENT);
            }
            
            // Commission dari entry
            total_commission += HistoryDealGetDouble(deal_ticket, DEAL_COMMISSION);
         }
         
         // Deal exit (closing position) - KUNCI PROFIT DISINI
         if(deal_entry == DEAL_ENTRY_OUT)
         {
            close_price = HistoryDealGetDouble(deal_ticket, DEAL_PRICE);
            close_time = (datetime)HistoryDealGetInteger(deal_ticket, DEAL_TIME);
            
            // ACCUMULATE semua profit components
            double deal_profit = HistoryDealGetDouble(deal_ticket, DEAL_PROFIT);
            double deal_swap = HistoryDealGetDouble(deal_ticket, DEAL_SWAP);
            double deal_commission = HistoryDealGetDouble(deal_ticket, DEAL_COMMISSION);
            
            total_profit += deal_profit;
            total_swap += deal_swap;
            total_commission += deal_commission;
            
            if(ENABLE_LOGS && (deal_profit != 0 || deal_swap != 0 || deal_commission != 0))
            {
               Print("  Deal #", deal_ticket, " P:", DoubleToString(deal_profit, 2), 
                     " S:", DoubleToString(deal_swap, 2), 
                     " C:", DoubleToString(deal_commission, 2));
            }
         }
         
         // Deal inout (untuk partial close)
         if(deal_entry == DEAL_ENTRY_INOUT)
         {
            double deal_profit = HistoryDealGetDouble(deal_ticket, DEAL_PROFIT);
            double deal_swap = HistoryDealGetDouble(deal_ticket, DEAL_SWAP);
            double deal_commission = HistoryDealGetDouble(deal_ticket, DEAL_COMMISSION);
            
            total_profit += deal_profit;
            total_swap += deal_swap;
            total_commission += deal_commission;
         }
      }
      
      // Restore history selection
      HistorySelect(backup_from, backup_to);
   }
   
   // FINAL PROFIT = profit + swap + commission
   double final_profit = total_profit + total_swap + total_commission;
   
   // Format close time
   MqlDateTime dt_struct;
   TimeToStruct(close_time, dt_struct);
   string close_time_str = StringFormat("%04d-%02d-%02d %02d:%02d:%02d", 
                                       dt_struct.year, dt_struct.mon, dt_struct.day,
                                       dt_struct.hour, dt_struct.min, dt_struct.sec);
   
   // Build JSON
   json += "\"ticket\":" + IntegerToString((long)position_id) + ",";
   json += "\"symbol\":\"" + symbol + "\",";
   json += "\"type\":\"" + position_type + "\",";
   json += "\"volume\":" + DoubleToString(volume, 2) + ",";
   json += "\"price\":" + DoubleToString(entry_price, _Digits) + ",";
   json += "\"profit\":" + DoubleToString(final_profit, 2) + ",";
   json += "\"time\":\"" + close_time_str + "\",";
   json += "\"comment\":\"" + EscapeJSON(comment) + "\"";
   json += "}";
   
   if(ENABLE_LOGS)
   {
      Print("Position #", position_id, " ", symbol, " ", position_type, 
            " Vol:", DoubleToString(volume, 2),
            " Profit:", DoubleToString(final_profit, 2),
            " [P:", DoubleToString(total_profit, 2),
            " S:", DoubleToString(total_swap, 2),
            " C:", DoubleToString(total_commission, 2), "]");
   }
   
   return json;
}

string GetDealType(long dealType)
{
   switch((int)dealType)
   {
      case DEAL_TYPE_BUY:        return "BUY";
      case DEAL_TYPE_SELL:       return "SELL";
      case DEAL_TYPE_BALANCE:    return "BALANCE";
      case DEAL_TYPE_CREDIT:     return "CREDIT";
      default:                   return "UNKNOWN";
   }
}

bool SendToAPI(string jsonData)
{
   char post[], result[];
   // Tambahkan token di header dan juga di URL sebagai query parameter (backup)
   string headers = "Content-Type: application/json\r\n";
   headers += "Token: " + API_TOKEN + "\r\n";
   
   // Tambahkan token di URL sebagai query parameter juga (untuk kompatibilitas)
   string url_with_token = API_URL;
   if(StringFind(API_URL, "?") == -1)
   {
      url_with_token += "?token=" + API_TOKEN;
   }
   else
   {
      url_with_token += "&token=" + API_TOKEN;
   }
   
   StringToCharArray(jsonData, post, 0, StringLen(jsonData));
   
   ResetLastError();
   int res = WebRequest("POST", url_with_token, headers, 5000, post, result, headers);
   
   if(res == -1)
   {
      int error = GetLastError();
      if(ENABLE_LOGS) 
      {
         Print("✗ WebRequest FAILED. Error: ", error);
         if(error == 4014) 
         {
            Print(">>> URL not allowed! Add this to MT5:");
            Print(">>> Tools -> Options -> Expert Advisors -> Allow WebRequest for:");
            Print(">>> ", API_URL);
         }
      }
      return false;
   }
   
   string response = CharArrayToString(result, 0, WHOLE_ARRAY);
   if(ENABLE_LOGS) Print("API Response: ", response);
   
   return true;
}

//+------------------------------------------------------------------+
